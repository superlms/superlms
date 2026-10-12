<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════ HEADER (analytics + tabs + filter) ══════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                        class="w-11 h-11 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Careers</h1>
                    </div>
                </div>
                <div class="flex items-center gap-4 flex-shrink-0">
                    {{-- Query analytics (become-executive style header stats) --}}
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                        <span class="px-4">New: <strong class="text-amber-500">{{ $analytics['new'] }}</strong></span>
                        <span class="px-4">Reviewed: <strong class="text-emerald-600">{{ $analytics['reviewed'] }}</strong></span>
                        <span class="pl-4">This Month: <strong class="text-blue-600">{{ $analytics['this_month'] }}</strong></span>
                    </div>
                    <button wire:click="openJobCreate"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add Job
                    </button>
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                <span>New: <strong class="text-amber-500">{{ $analytics['new'] }}</strong></span>
                <span>Reviewed: <strong class="text-emerald-600">{{ $analytics['reviewed'] }}</strong></span>
                <span>This Month: <strong class="text-blue-600">{{ $analytics['this_month'] }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="switchTab('jobs')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'jobs' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Job Openings
                        <span class="ml-1 text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ $activeTab === 'jobs' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">{{ count($jobs) }}</span>
                    </span>
                </button>
                <button wire:click="switchTab('applications')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'applications' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Website Queries
                        @if ($newCount > 0)
                            <span class="ml-1 text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ $activeTab === 'applications' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700' }}">{{ $newCount }} new</span>
                        @endif
                    </span>
                </button>
            </div>
        </div>

        {{-- Filter bar (FAQ-style, queries tab only) --}}
        @if ($activeTab === 'applications')
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
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email, role…"
                            class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-full focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <select wire:model.live="statusFilter"
                        class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All statuses</option>
                        <option value="new">New</option>
                        <option value="reviewed">Reviewed</option>
                    </select>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 hidden sm:inline">Last:</span>
                        <div class="flex gap-1">
                            @foreach ([7, 15, 30] as $days)
                                <button wire:click="$set('filterDays', '{{ $filterDays == $days ? '' : $days }}')"
                                    class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                           {{ $filterDays == $days ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                    {{ $days }}d
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if ($search || $filterDays || $statusFilter)
                        <button wire:click="clearFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════ TAB 1 — JOB OPENINGS ══════════════════ --}}
    {{-- In the Website Enquiries' table look: S.No, the opening (its icon, the
         role, under it the department and location, then, small, the type and
         salary) and the actions. --}}
    @if ($activeTab === 'jobs')
        <div class="p-4 sm:p-6">
            @if (empty($jobs))
                <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
                    <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-gray-800">No job openings yet</p>
                    <p class="text-sm text-gray-400 mt-1">Click <span class="font-semibold text-indigo-600">Add Job</span> to publish your first opening.</p>
                </div>
            @else
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Job Opening</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($jobs as $index => $job)
                                    @php
                                        $jobWhere = collect([$job['department'] ?? '', $job['location'] ?? ''])->filter()->implode(' · ');
                                        $jobTerms = collect([$job['type'] ?? '', $job['salary'] ?? ''])->filter()->implode(' · ');
                                    @endphp
                                    <tr wire:key="job-{{ $index }}" class="hover:bg-gray-50/70 transition-colors">

                                        {{-- S.No --}}
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-500 font-medium">{{ $index + 1 }}</span>
                                        </td>

                                        {{-- Job opening --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $job['role'] ?: '—' }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $jobWhere !== '' ? $jobWhere : '—' }}</p>
                                                    @if ($jobTerms !== '')
                                                        <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $jobTerms }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                <button wire:click="openJobEdit({{ $index }})" title="Edit"
                                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="confirmJobDelete({{ $index }})" title="Delete"
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ══════════════════ TAB 2 — WEBSITE QUERIES ══════════════════ --}}
    {{-- In the Website Enquiries' table look: S.No, the application (its icon,
         the applicant, under it the message on one line cut short with …,
         then, small, the role, how to reach them, when, new or reviewed and a
         resume) and the actions. The rest is on its View; a click on the row
         opens it. --}}
    @if ($activeTab === 'applications')
        <div class="p-4 sm:p-6">
            @if ($applications->count())
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Application</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($applications as $index => $app)
                                    @php $isReviewed = $app->status === 'reviewed'; @endphp
                                    <tr wire:key="capp-{{ $app->id }}" wire:click="viewApplication({{ $app->id }})"
                                        class="hover:bg-gray-50/70 transition-colors cursor-pointer">

                                        {{-- S.No --}}
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-500 font-medium">{{ $applications->firstItem() + $index }}</span>
                                        </td>

                                        {{-- Application --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $app->full_name ?: 'Anonymous' }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $app->description ?: '—' }}</p>
                                                    <p class="text-[11px] text-gray-400 truncate mt-0.5">
                                                        {{ $app->job_role ?: 'General application' }}
                                                        @if ($app->email)
                                                            <span class="mx-1 text-gray-300">·</span>{{ $app->email }}
                                                        @endif
                                                        @if ($app->mobile)
                                                            <span class="mx-1 text-gray-300">·</span>{{ $app->mobile }}
                                                        @endif
                                                        <span class="mx-1 text-gray-300">·</span>
                                                        {{ $app->created_at->format('D, d M Y · g:i A') }}
                                                        <span class="mx-1 text-gray-300">·</span>
                                                        <span class="{{ $isReviewed ? 'text-emerald-600' : 'text-amber-600' }}">{{ $isReviewed ? 'Reviewed' : 'New' }}</span>
                                                        @if ($app->document_path)
                                                            <span class="mx-1 text-gray-300">·</span>
                                                            Resume
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-4 py-3" onclick="event.stopPropagation()">
                                            <div class="flex items-center justify-center gap-1">
                                                <button wire:click="viewApplication({{ $app->id }})" title="View"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                @if ($app->document_path)
                                                    <button wire:click="viewDocument({{ $app->id }})" title="View Resume"
                                                        class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                                <button wire:click="confirmDeleteApplication({{ $app->id }})" title="Delete"
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
                    <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-gray-800">No applications found</p>
                    <p class="text-sm text-gray-400 mt-1">Applications submitted from the Careers page will appear here.</p>
                </div>
            @endif

            @if ($applications->hasPages())
                <div class="mt-6">{{ $applications->links() }}</div>
            @endif
        </div>
    @endif

    {{-- ══════════════════ JOB SLIDE-IN PANEL ══════════════════ --}}
    {{-- Teleported to <body> so the overlay sits above the fixed navbar/sidebar
         (see faqs.blade.php) — otherwise the panel's close button is dead under
         the navbar and outside clicks never reach the backdrop. --}}
    @if ($showJobPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data @keydown.escape.window="$wire.closeJobPanel()">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closeJobPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $jobEditIndex !== null ? 'Edit Job Opening' : 'Add Job Opening' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">This appears on the public Careers page.</p>
                    </div>
                    <button wire:click="closeJobPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role / Title <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="jobRole" placeholder="Business Development Executive"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            @error('jobRole') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Salary</label>
                            <input type="text" wire:model="jobSalary" placeholder="₹3–6 LPA + incentives"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" wire:model="jobDepartment" placeholder="Sales"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <input type="text" wire:model="jobType" placeholder="Full-time"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" wire:model="jobLocation" placeholder="Aligarh / Remote"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-100 flex items-center justify-end gap-3 flex-shrink-0">
                    <button wire:click="closeJobPanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="saveJob" type="button" wire:loading.attr="disabled" wire:target="saveJob"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                        <span wire:loading.remove wire:target="saveJob">{{ $jobEditIndex !== null ? 'Update Job' : 'Add Job' }}</span>
                        <span wire:loading wire:target="saveJob">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ APPLICATION VIEW SLIDE-IN PANEL ══════════════════ --}}
    {{-- In the Website Enquiry view's look: the applicant in the header, then
         plain label / value rows; the resume under a line. --}}
    @if ($viewing)
        @php $cvReviewed = $viewing->status === 'reviewed'; @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data @keydown.escape.window="$wire.closeApplication()">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeApplication"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewing->full_name ?: 'Application Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ $viewing->job_role ?: 'General application' }} · {{ $cvReviewed ? 'Reviewed' : 'New' }}
                        </p>
                    </div>
                    <button wire:click="closeApplication"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body — plain label / value rows --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @foreach ([
                        'Applied for'   => $viewing->job_role ?: 'General application',
                        'Email'         => $viewing->email ?: '—',
                        'Mobile'        => $viewing->mobile ?: '—',
                        'Experience'    => $viewing->experience ?: '—',
                        'Qualification' => $viewing->qualification ?: '—',
                        'Applied on'    => $viewing->created_at->format('d M Y · g:i A'),
                    ] as $label => $value)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                            <span class="col-span-2 text-gray-800 font-medium break-words">{{ $value }}</span>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Status</span>
                        <span class="col-span-2 font-medium {{ $cvReviewed ? 'text-emerald-600' : 'text-amber-600' }}">{{ $cvReviewed ? 'Reviewed' : 'New' }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Address</span>
                        <span class="col-span-2 text-gray-800 whitespace-pre-line break-words">{{ $viewing->address ?: '—' }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">About / Message</span>
                        <span class="col-span-2 text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $viewing->description ?: '—' }}</span>
                    </div>

                    {{-- The resume, under a line --}}
                    <div class="pt-4 mt-2 border-t border-gray-100">
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Resume</span>
                            @if ($viewing->document_path)
                                <span class="col-span-2 font-medium">
                                    <button type="button" wire:click="viewDocument({{ $viewing->id }})" class="text-blue-600 hover:underline">Open in new tab ↗</button>
                                    <span class="mx-1.5 text-gray-300">·</span>
                                    <button type="button" wire:click="downloadDocument({{ $viewing->id }})" class="text-blue-600 hover:underline">Download</button>
                                </span>
                            @else
                                <span class="col-span-2 text-gray-400 font-medium">Not attached</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button type="button" wire:click="toggleReviewed({{ $viewing->id }})"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        {{ $cvReviewed ? 'Mark as New' : 'Mark as Reviewed' }}
                    </button>
                    <button type="button" wire:click="closeApplication" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ DELETE JOB CONFIRM ══════════════════ --}}
    @if ($pendingJobDelete !== null)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Job Opening</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">Are you sure you want to remove this job opening from the website? This cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteJob" class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Yes, Delete</button>
                    <button wire:click="cancelJobDelete" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ DELETE APPLICATION CONFIRM ══════════════════ --}}
    @if ($pendingAppDelete !== null)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Application</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">Are you sure you want to delete this application? This will also remove the attached document and cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteApplication" class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Yes, Delete</button>
                    <button wire:click="cancelDeleteApplication" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Open document in a new browser tab (second screen) --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-document', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                if (data && data.url) window.open(data.url, '_blank', 'noopener');
            });
        });
    </script>
</div>
