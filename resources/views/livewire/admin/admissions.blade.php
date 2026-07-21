<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <x-admin.back-to-more />
                    <div>
                        <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Admissions</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Manage student admissions and forms</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                        <span class="px-4">Updated: <strong class="text-emerald-600">{{ $analytics['updated'] }}</strong></span>
                        <span class="px-4">This Month: <strong class="text-blue-600">{{ $analytics['this_month'] }}</strong></span>
                        <span class="pl-4">Last Month: <strong class="text-gray-800">{{ $analytics['last_month'] }}</strong></span>
                    </div>

                    @if ($activeTab === 'admissions')
                        <button wire:click="openEnquiryModal"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Add Student</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @else
                        <button wire:click="openPaperModal"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span class="hidden sm:inline">Upload Form</span>
                            <span class="sm:hidden">Upload</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                <span>Updated: <strong class="text-emerald-600">{{ $analytics['updated'] }}</strong></span>
                <span>This Month: <strong class="text-blue-600">{{ $analytics['this_month'] }}</strong></span>
                <span>Last Month: <strong class="text-gray-800">{{ $analytics['last_month'] }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="setTab('admissions')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'admissions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Admissions
                    </span>
                </button>
                <button wire:click="setTab('papers')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'papers' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Admission Form
                    </span>
                </button>
            </div>
        </div>

        {{-- Filter bar --}}
        @if ($activeTab === 'admissions')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter:
                    </div>

                    <select wire:model.live="filterMonth"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Months</option>
                        @foreach ($monthOptions as $m)
                            <option value="{{ $m['value'] }}">{{ $m['label'] }}</option>
                        @endforeach
                    </select>

                    <span class="text-gray-300">→</span>

                    <select wire:model.live="filterStandard" @disabled(!$filterMonth)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>

                    <div class="h-5 w-px bg-gray-300 hidden sm:block"></div>

                    <div class="flex items-center gap-1.5 flex-1 max-w-md">
                        <input wire:model.defer="searchInput" wire:keydown.enter="applySearch" type="text"
                            placeholder="Search name, mobile, guardian..."
                            class="flex-1 text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <button wire:click="applySearch"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Search
                        </button>
                    </div>

                    @if ($search || $filterStandard || $filterMonth)
                        <button wire:click="clearAllFilters"
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
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">Filter:</div>
                    <select wire:model.live="filterPaperStandard"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>
                    @if ($filterPaperStandard)
                        <button wire:click="$set('filterPaperStandard', '')"
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

        @if ($activeTab === 'admissions')
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Guardian</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mobile</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Added</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($enquiries as $enquiry)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-900">{{ $enquiry->student_name }}</p>
                                        @if ($enquiry->email)
                                            <p class="text-xs text-gray-500">{{ $enquiry->email }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enquiry->guardian_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enquiry->mobile }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enquiry->standard->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold text-gray-800">₹{{ $enquiry->admission_fee }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide
                                            {{ $enquiry->status === 'updated' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $enquiry->status === 'updated' ? 'Updated' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $enquiry->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="viewEnquiry({{ $enquiry->id }})" title="View"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button wire:click="downloadAdmissionForm({{ $enquiry->id }})" title="Download Admission Form"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </button>
                                            <button wire:click="openFeeModal({{ $enquiry->id }})" title="Update Fee"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-green-50 hover:text-green-600 hover:border-green-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <button wire:click="openUpdateModal({{ $enquiry->id }})" title="Update Result"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                </svg>
                                            </button>
                                            <button wire:click="editEnquiry({{ $enquiry->id }})" title="Edit"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button wire:click="deleteEnquiry({{ $enquiry->id }})" title="Delete"
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
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800">No admission enquiries found</p>
                                        <p class="text-xs text-gray-400 mt-1">Click "Add Student" to create the first entry.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($enquiries->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $enquiries->links() }}</div>
                @endif
            </div>
        @else
            {{-- ═════ EXAM PAPERS TAB ═════ --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
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
                                            <span class="text-sm font-semibold text-gray-900">{{ $paper->title ?? 'Untitled' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $paper->standard->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $paper->created_at->format('d M Y, g:i A') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="downloadExamPaper({{ $paper->id }})" title="Download"
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
                                            <button wire:click="deleteExamPaper({{ $paper->id }})" title="Delete"
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
                                    <td colspan="4" class="px-4 py-16 text-center">
                                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800">No admission forms uploaded</p>
                                        <p class="text-xs text-gray-400 mt-1">Click "Upload Form" to add the first form.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT STUDENT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($enquiryModalOpen)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEnquiryModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editEnquiryId ? 'Edit Student' : 'Add Student' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editEnquiryId ? 'Update enquiry details' : 'New admission enquiry' }}</p>
                    </div>
                    <button wire:click="closeEnquiryModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Student Name <span class="text-red-500">*</span></label>
                            <input wire:model.defer="studentName" type="text" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('studentName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input wire:model.defer="email" type="email" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mobile" type="text" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('mobile')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Guardian Name <span class="text-red-500">*</span></label>
                            <input wire:model.defer="guardianName" type="text" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('guardianName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class</label>
                            <select wire:model.defer="standardId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Class</option>
                                @foreach ($standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Stream</label>
                            <input wire:model.defer="stream" type="text" placeholder="e.g. Science, Commerce" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Admission Fee <span class="text-red-500">*</span></label>
                            <input wire:model.defer="admissionFee" type="number" min="0" step="0.01" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('admissionFee')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Mode</label>
                            <select wire:model.defer="paymentMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white">
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                            </select>
                            @error('paymentMode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected On</label>
                            <input wire:model.defer="feeCollectedAt" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('feeCollectedAt')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected By</label>
                            <input wire:model.defer="collectedBy" type="text" placeholder="Name of person who collected" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('collectedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                            <textarea wire:model.defer="address" rows="3" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEnquiryModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveEnquiry" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="saveEnquiry">{{ $editEnquiryId ? 'Update' : 'Add Student' }}</span>
                        <span wire:loading wire:target="saveEnquiry">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         UPDATE RESULT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($updateModalOpen)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeUpdateModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Update Result</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Enter marks and optional result PDF</p>
                    </div>
                    <button wire:click="closeUpdateModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Total Marks <span class="text-red-500">*</span></label>
                            <input wire:model.defer="totalMarks" type="number" min="0" step="0.01" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('totalMarks')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Obtained Marks <span class="text-red-500">*</span></label>
                            <input wire:model.defer="obtainedMarks" type="number" min="0" step="0.01" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('obtainedMarks')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Remarks</label>
                        <textarea wire:model.defer="remarks" rows="3" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Result PDF <span class="text-gray-400 font-normal">(Optional, max 10MB)</span></label>
                        <input wire:model="resultPdf" type="file" accept=".pdf" class="w-full text-sm">
                        <div wire:loading wire:target="resultPdf" class="text-xs text-blue-600 mt-1">Uploading...</div>
                        @error('resultPdf')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeUpdateModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveUpdate" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveUpdate">Save Result</span>
                        <span wire:loading wire:target="saveUpdate">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW STUDENT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($viewModalOpen && !empty($viewEnquiryData))
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="block w-2 h-2 rounded-full {{ $viewEnquiryData['status'] === 'updated' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewEnquiryData['student_name'] }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Admitted: {{ $viewEnquiryData['created_at'] }}</p>
                        </div>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div class="grid grid-cols-2 gap-6">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Email</p><p class="text-sm text-gray-800 truncate">{{ $viewEnquiryData['email'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Mobile</p><p class="text-sm text-gray-800">{{ $viewEnquiryData['mobile'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Guardian</p><p class="text-sm text-gray-800">{{ $viewEnquiryData['guardian_name'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Class</p><p class="text-sm text-gray-800">{{ $viewEnquiryData['class'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Stream</p><p class="text-sm text-gray-800">{{ $viewEnquiryData['stream'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Admission Fee</p><p class="text-sm font-semibold text-gray-800">₹{{ $viewEnquiryData['admission_fee'] }}</p></div>
                    </div>
                    <div class="border-t border-gray-100 pt-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Address</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $viewEnquiryData['address'] }}</p>
                    </div>
                    @if ($viewEnquiryData['status'] === 'updated')
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Result</p>
                            <div class="grid grid-cols-2 gap-6">
                                <div><p class="text-xs text-gray-500">Total Marks</p><p class="text-sm font-semibold text-gray-800">{{ $viewEnquiryData['total_marks'] }}</p></div>
                                <div><p class="text-xs text-gray-500">Obtained Marks</p><p class="text-sm font-semibold text-emerald-600">{{ $viewEnquiryData['obtained_marks'] }}</p></div>
                            </div>
                            @if ($viewEnquiryData['remarks'] && $viewEnquiryData['remarks'] !== '—')
                                <div class="mt-3 bg-gray-50 border-l-2 border-blue-500 rounded-r-md px-4 py-3">
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Remarks</p>
                                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $viewEnquiryData['remarks'] }}</p>
                                </div>
                            @endif
                            @if ($viewEnquiryData['result_pdf'])
                                <button wire:click="downloadResultPdf({{ $viewEnquiryData['id'] }})"
                                    class="mt-3 inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download Result PDF
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         UPLOAD EXAM PAPER SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($paperModalOpen)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePaperModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Upload Admission Form</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Maximum size: 1 MB · PDF only</p>
                    </div>
                    <button wire:click="closePaperModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input wire:model.defer="paperTitle" type="text" placeholder="e.g. Class 10 Admission Form"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('paperTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.defer="paperStandardId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">Select Class</option>
                            @foreach ($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                        @error('paperStandardId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">PDF File <span class="text-red-500">*</span> <span class="text-xs font-normal text-gray-400">(max 1 MB)</span></label>
                        <input wire:model="paperFile" type="file" accept=".pdf" class="w-full text-sm">
                        <p class="text-xs text-gray-400 mt-1">File size parameter: <strong>1 MB</strong> ({{ number_format(1024 * 1024) }} bytes)</p>
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
                    <button wire:click="saveExamPaper" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveExamPaper">Upload</span>
                        <span wire:loading wire:target="saveExamPaper">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         EDIT EXAM PAPER SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($editPaperModalOpen)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditPaperModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Edit Admission Form</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Replace PDF only if needed (max 1 MB)</p>
                    </div>
                    <button wire:click="closeEditPaperModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input wire:model.defer="editPaperTitle" type="text" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('editPaperTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.defer="editPaperStandardId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">Select Class</option>
                            @foreach ($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                        @error('editPaperStandardId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Replace PDF <span class="text-gray-400 font-normal">(Optional, max 1 MB)</span></label>
                        <input wire:model="editPaperFile" type="file" accept=".pdf" class="w-full text-sm">
                        <p class="text-xs text-gray-400 mt-1">Leave empty to keep existing PDF.</p>
                        @if ($editPaperFile)
                            <div class="mt-2 text-xs text-emerald-600">
                                Selected: {{ $editPaperFile->getClientOriginalName() }}
                                ({{ number_format($editPaperFile->getSize() / 1024, 2) }} KB)
                            </div>
                        @endif
                        @error('editPaperFile')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEditPaperModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveEditPaper" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveEditPaper">Update</span>
                        <span wire:loading wire:target="saveEditPaper">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         FEE COLLECTION SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($feeModalOpen)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeFeeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Update Fee Collection</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Record how much was collected, the mode, and by whom.</p>
                    </div>
                    <button wire:click="closeFeeModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount Collected (₹) <span class="text-red-500">*</span></label>
                        <input wire:model="collectedAmount" type="number" min="0" step="0.01"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-green-500 focus:border-green-500">
                        @error('collectedAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Mode <span class="text-red-500">*</span></label>
                            <select wire:model="paymentMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white">
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                            </select>
                            @error('paymentMode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected On <span class="text-red-500">*</span></label>
                            <input wire:model="feeCollectedAt" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-green-500 focus:border-green-500">
                            @error('feeCollectedAt')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected By <span class="text-red-500">*</span></label>
                        <input wire:model="collectedBy" type="text" placeholder="Name of person who collected"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-green-500 focus:border-green-500">
                        @error('collectedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeFeeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveFee" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAYS
    ══════════════════════════════════════════════════ --}}
    @if ($pendingDeleteEnquiryId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteEnquiry"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete enquiry?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the student enquiry and any uploaded result PDF.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteEnquiry" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="doDeleteEnquiry" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingDeletePaperId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeletePaper"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete admission form?</h3>
                        <p class="text-sm text-gray-500">The PDF will be permanently removed from storage.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeletePaper" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="doDeleteExamPaper" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

</div>
