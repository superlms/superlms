<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════ HEADER (analytics + tabs + filter) ══════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                        class="w-11 h-11 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Careers</h1>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">Manage job openings and applications submitted from the website.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button wire:click="openJobCreate"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add Job
                    </button>
                </div>
            </div>

            {{-- Analytics (queries tab) --}}
            @if ($activeTab === 'applications')
                <div class="flex items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                    <span>Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                    <span>New: <strong class="text-amber-500">{{ $analytics['new'] }}</strong></span>
                    <span>Reviewed: <strong class="text-emerald-600">{{ $analytics['reviewed'] }}</strong></span>
                    <span>This Month: <strong class="text-blue-600">{{ $analytics['this_month'] }}</strong></span>
                </div>
            @endif
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

                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email, role…"
                            class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-60 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <select wire:model.live="statusFilter"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════ TAB 1 — JOB OPENINGS ══════════════════ --}}
    @if ($activeTab === 'jobs')
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
            @if (empty($jobs))
                <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
                    <div class="w-14 h-14 mx-auto mb-3 bg-indigo-50 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-gray-800">No job openings yet</p>
                    <p class="text-sm text-gray-400 mt-1">Click <span class="font-semibold text-indigo-600">Add Job</span> to publish your first opening.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($jobs as $index => $job)
                        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 shadow-sm hover:shadow-md transition-shadow flex items-start justify-between gap-4" wire:key="job-{{ $index }}">
                            <div class="min-w-0">
                                <div class="text-base font-semibold text-gray-900">{{ $job['role'] ?: '—' }}</div>
                                @if (!empty($job['salary']))
                                    <div class="text-sm font-semibold text-indigo-600 mt-0.5">💰 {{ $job['salary'] }}</div>
                                @endif
                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                    @if (!empty($job['department']))
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">{{ $job['department'] }}</span>
                                    @endif
                                    @if (!empty($job['location']))
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-pink-50 text-pink-600">{{ $job['location'] }}</span>
                                    @endif
                                    @if (!empty($job['type']))
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $job['type'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button wire:click="openJobEdit({{ $index }})" title="Edit"
                                    class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="confirmJobDelete({{ $index }})" title="Delete"
                                    class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ══════════════════ TAB 2 — WEBSITE QUERIES ══════════════════ --}}
    @if ($activeTab === 'applications')
        <div class="p-4 sm:p-6">
            <div class="space-y-3">
                @forelse ($applications as $app)
                    @php $isReviewed = $app->status === 'reviewed'; @endphp
                    <div wire:click="viewApplication({{ $app->id }})"
                        class="group bg-white rounded-xl border border-gray-200 hover:border-indigo-200 hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer" wire:key="capp-{{ $app->id }}">
                        <div class="flex items-stretch">
                            <div class="w-1 flex-shrink-0 {{ $isReviewed ? 'bg-emerald-500' : 'bg-amber-400' }}"></div>

                            <div class="flex-1 p-4 sm:p-5 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3 flex-1 min-w-0">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $isReviewed ? 'bg-emerald-50' : 'bg-amber-50' }}">
                                            <svg class="w-5 h-5 {{ $isReviewed ? 'text-emerald-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                <h3 class="text-base font-semibold text-gray-900">{{ $app->full_name }}</h3>
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide {{ $isReviewed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $isReviewed ? 'Reviewed' : 'New' }}</span>
                                                @if ($app->job_role)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">💼 {{ \Illuminate\Support\Str::limit($app->job_role, 30) }}</span>
                                                @endif
                                            </div>

                                            @if ($app->description)
                                                <p class="text-sm text-gray-600 line-clamp-2 mb-2.5 leading-relaxed">{{ \Illuminate\Support\Str::limit($app->description, 160) }}</p>
                                            @endif

                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                                @if ($app->email)
                                                    <span class="inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                                        <span class="font-medium text-gray-600">{{ $app->email }}</span>
                                                    </span>
                                                    <span class="text-gray-300">•</span>
                                                @endif
                                                @if ($app->mobile)
                                                    <span class="inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                                        {{ $app->mobile }}
                                                    </span>
                                                    <span class="text-gray-300">•</span>
                                                @endif
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    {{ $app->created_at->format('M j, Y · g:i A') }}
                                                </span>
                                                @if ($app->document_path)
                                                    <span class="text-gray-300">•</span>
                                                    <span class="inline-flex items-center gap-1 text-indigo-500">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                                        Resume
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 flex-shrink-0" @click.stop>
                                        <button wire:click="viewApplication({{ $app->id }})" title="View"
                                            class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        @if ($app->document_path)
                                            <button wire:click="viewDocument({{ $app->id }})" title="View Resume"
                                                class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click="confirmDeleteApplication({{ $app->id }})" title="Delete"
                                            class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
                        <div class="w-14 h-14 mx-auto mb-3 bg-indigo-50 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <p class="text-base font-semibold text-gray-800">No applications found</p>
                        <p class="text-sm text-gray-400 mt-1">Applications submitted from the Careers page will appear here.</p>
                    </div>
                @endforelse
            </div>

            @if ($applications->hasPages())
                <div class="mt-6">{{ $applications->links() }}</div>
            @endif
        </div>
    @endif

    {{-- ══════════════════ JOB SLIDE-IN PANEL ══════════════════ --}}
    @if ($showJobPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
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
    @if ($viewing)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closeApplication"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $viewing->full_name }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $viewing->job_role ?: 'General application' }}</p>
                    </div>
                    <button wire:click="closeApplication"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Email</p>
                            <p class="text-sm text-gray-800 break-all">{{ $viewing->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Mobile</p>
                            <p class="text-sm text-gray-800">{{ $viewing->mobile }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Experience</p>
                            <p class="text-sm text-gray-800">{{ $viewing->experience ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Qualification</p>
                            <p class="text-sm text-gray-800">{{ $viewing->qualification ?: '—' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Applied On</p>
                            <p class="text-sm text-gray-800">{{ $viewing->created_at->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Address</p>
                        <p class="text-sm text-gray-800 whitespace-pre-line">{{ $viewing->address ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">About / Message</p>
                        <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $viewing->description ?: '—' }}</p>
                    </div>

                    @if ($viewing->document_path)
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Resume / Document</p>
                            <div class="flex items-center gap-2">
                                <button wire:click="viewDocument({{ $viewing->id }})"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    View in new tab
                                </button>
                                <button wire:click="downloadDocument({{ $viewing->id }})"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors">
                                    ⬇ Download
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeApplication" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="toggleReviewed({{ $viewing->id }})"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                        {{ $viewing->status === 'reviewed' ? 'Mark as New' : 'Mark as Reviewed' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ DELETE JOB CONFIRM ══════════════════ --}}
    @if ($pendingJobDelete !== null)
        <div class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
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
        <div class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
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
