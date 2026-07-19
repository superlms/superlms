<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + tabs + filter)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Website Enquiries</h1>
                </div>
                @php
                    $isDemo  = $activeTab === 'demo';
                    $totalTab = $isDemo ? $analytics['demo'] : $analytics['contact'];
                    $pendingTab = $isDemo ? $analytics['demo_pending'] : $analytics['contact_pending'];
                    $remarkedTab = $isDemo ? $analytics['demo_remarked'] : $analytics['contact_remarked'];
                    $thisMonthTab = $isDemo ? $analytics['demo_this_month'] : $analytics['contact_this_month'];
                @endphp
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalTab }}</strong></span>
                    <span class="px-4">Pending: <strong class="text-amber-500">{{ $pendingTab }}</strong></span>
                    <span class="px-4">Remarked: <strong class="text-emerald-600">{{ $remarkedTab }}</strong></span>
                    <span class="pl-4">This Month: <strong class="text-blue-600">{{ $thisMonthTab }}</strong></span>
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $totalTab }}</strong></span>
                <span>Pending: <strong class="text-amber-500">{{ $pendingTab }}</strong></span>
                <span>Remarked: <strong class="text-emerald-600">{{ $remarkedTab }}</strong></span>
                <span>This Month: <strong class="text-blue-600">{{ $thisMonthTab }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="switchTab('demo')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'demo' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Demo Enquiries
                        <span class="ml-1 text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ $activeTab === 'demo' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">{{ $analytics['demo'] }}</span>
                    </span>
                </button>
                <button wire:click="switchTab('contact')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'contact' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Contact Enquiries
                        <span class="ml-1 text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ $activeTab === 'contact' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">{{ $analytics['contact'] }}</span>
                    </span>
                </button>
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

                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, email, school..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden sm:inline">Last:</span>
                    <div class="flex gap-1">
                        @foreach ([7, 15, 30] as $days)
                            <button wire:click="$set('filterDays', '{{ $filterDays == $days ? '' : $days }}')"
                                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                       {{ $filterDays == $days
                                           ? 'bg-blue-600 text-white'
                                           : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $days }}d
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="h-5 w-px bg-gray-300 hidden sm:block"></div>

                <select wire:model.live="statusFilter"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="remarked">Remarked</option>
                </select>

                @if ($search || $filterDays || $statusFilter)
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
         BODY — Enquiry list
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="space-y-3">
            @forelse ($enquiries as $enquiry)
                @php
                    $isRemarked = !empty($enquiry->remark);
                    $isScheduled = $activeTab === 'demo' && !empty($enquiry->preferred_date);
                    $bodyText   = $activeTab === 'demo'
                        ? ($enquiry->role ?? '') . ($enquiry->no_of_students ? ' · ' . $enquiry->no_of_students . ' students' : '')
                        : ($enquiry->description ?? '');
                @endphp
                <div class="group rounded-xl border hover:shadow-md transition-all duration-200 overflow-hidden
                    {{ $isScheduled ? 'bg-indigo-50/50 border-indigo-200 hover:border-indigo-300' : 'bg-white border-gray-200 hover:border-blue-200' }}">
                    <div class="flex items-stretch">
                        <div class="w-1 flex-shrink-0 {{ $isScheduled ? 'bg-indigo-500' : ($isRemarked ? 'bg-emerald-500' : 'bg-amber-400') }}"></div>

                        <div class="flex-1 p-4 sm:p-5 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3 flex-1 min-w-0">

                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $isRemarked ? 'bg-emerald-50' : 'bg-amber-50' }}">
                                        @if ($isRemarked)
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h3 class="text-base font-semibold text-gray-900">{{ $enquiry->full_name ?? 'Anonymous' }}</h3>
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide
                                                {{ $isRemarked ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $isRemarked ? 'Remarked' : 'Pending' }}
                                            </span>
                                            @if ($isScheduled)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-indigo-100 text-indigo-700">
                                                    📞 Scheduled Call
                                                </span>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-700 bg-white border border-indigo-200 px-2 py-0.5 rounded-full">
                                                    📅 {{ $enquiry->preferred_date?->format('D, d M Y') }}
                                                    @if ($enquiry->preferred_time)· ⏰ {{ $enquiry->preferred_time }}@endif
                                                </span>
                                            @endif
                                            @if ($enquiry->school_name)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                    {{ Str::limit($enquiry->school_name, 30) }}
                                                </span>
                                            @endif
                                        </div>

                                        @if ($activeTab === 'contact' && $enquiry->subject)
                                            <p class="text-sm font-medium text-gray-700 mb-1">{{ $enquiry->subject }}</p>
                                        @endif

                                        @if ($bodyText)
                                            <p class="text-sm text-gray-600 line-clamp-2 mb-2.5 leading-relaxed">
                                                {{ Str::limit($bodyText, 160) }}
                                            </p>
                                        @endif

                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                            @if ($enquiry->email)
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="font-medium text-gray-600">{{ $enquiry->email }}</span>
                                                </span>
                                                <span class="text-gray-300">•</span>
                                            @endif

                                            @php
                                                $phone = $activeTab === 'demo' ? ($enquiry->phone ?? null) : ($enquiry->phone_number ?? null);
                                            @endphp
                                            @if ($phone)
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                    {{ $phone }}
                                                </span>
                                                <span class="text-gray-300">•</span>
                                            @endif

                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ $enquiry->created_at->format('M j, Y · g:i A') }}
                                            </span>
                                            <span class="text-gray-300">•</span>
                                            <span class="text-gray-400">{{ $enquiry->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button wire:click="viewEnquiry({{ $enquiry->id }})" title="View"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click="openRemarkModal({{ $enquiry->id }})" title="{{ $isRemarked ? 'Edit Remark' : 'Add Remark' }}"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="deleteEnquiry({{ $enquiry->id }})" title="Delete"
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
                    <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-gray-800">No {{ $activeTab === 'demo' ? 'demo' : 'contact' }} enquiries found</p>
                    <p class="text-sm text-gray-400 mt-1">Website submissions will appear here.</p>
                </div>
            @endforelse
        </div>

        @if ($enquiries->hasPages())
            <div class="mt-6">{{ $enquiries->links() }}</div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showDetailModal && $selectedEnquiry)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDetailModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Enquiry Details</h2></div>
                    <button wire:click="closeDetailModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Name</p>
                            <p class="text-sm text-gray-800">{{ $selectedEnquiry->full_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Email</p>
                            <p class="text-sm text-gray-800 truncate">{{ $selectedEnquiry->email ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Phone</p>
                            <p class="text-sm text-gray-800">{{ $activeTab === 'demo' ? ($selectedEnquiry->phone ?? '—') : ($selectedEnquiry->phone_number ?? '—') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">School</p>
                            <p class="text-sm text-gray-800 truncate">{{ $selectedEnquiry->school_name ?? '—' }}</p>
                        </div>
                        @if ($activeTab === 'demo')
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">City</p>
                                <p class="text-sm text-gray-800">{{ $selectedEnquiry->city ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Role</p>
                                <p class="text-sm text-gray-800">{{ $selectedEnquiry->role ?? '—' }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Number of Students</p>
                                <p class="text-sm text-gray-800">{{ $selectedEnquiry->no_of_students ?? '—' }}</p>
                            </div>
                            @if ($selectedEnquiry->preferred_date)
                                <div class="col-span-2 bg-indigo-50 border border-indigo-200 rounded-lg p-3 -mt-1">
                                    <p class="text-xs text-indigo-500 uppercase tracking-wider mb-1 font-semibold">📞 Scheduled Call</p>
                                    <p class="text-sm text-indigo-900 font-medium">
                                        📅 {{ $selectedEnquiry->preferred_date?->format('l, d M Y') }}
                                        @if ($selectedEnquiry->preferred_time)<br>⏰ {{ $selectedEnquiry->preferred_time }}@endif
                                    </p>
                                </div>
                            @endif
                        @endif
                    </div>

                    @if ($activeTab === 'contact')
                        <div class="border-t border-gray-100 pt-6">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Subject</p>
                            <p class="text-base font-medium text-gray-900">{{ $selectedEnquiry->subject ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Description</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $selectedEnquiry->description ?? '—' }}</p>
                        </div>
                    @endif

                    @if ($selectedEnquiry->remark)
                        <div class="border-t border-gray-100 pt-6">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Internal Remark</p>
                                <span class="text-xs text-gray-400">{{ $selectedEnquiry->updated_at->format('d M Y · g:i A') }}</span>
                            </div>
                            <div class="bg-gray-50 border-l-2 border-blue-500 rounded-r-md px-4 py-3">
                                <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $selectedEnquiry->remark }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2.5 text-sm text-amber-700 border-t border-gray-100 pt-6">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            No remark added yet
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button wire:click="openRemarkModal({{ $selectedEnquiry->id }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $selectedEnquiry->remark ? 'Edit Remark' : 'Add Remark' }}
                    </button>
                    <button wire:click="closeDetailModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         REMARK SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showRemarkModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeRemarkModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Add Remark</h2></div>
                    <button wire:click="closeRemarkModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="remarkText" rows="8" placeholder="Type your internal note here..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('remarkText')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeRemarkModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveRemark" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveRemark">Save Remark</span>
                        <span wire:loading wire:target="saveRemark">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($pendingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete enquiry?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the enquiry record. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="executeDelete">Delete</span>
                        <span wire:loading wire:target="executeDelete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
