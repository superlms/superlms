<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════ HEADER (analytics + filter) ══════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                        class="w-11 h-11 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Become an Executive</h1>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">Partner applications submitted from the website.</p>
                    </div>
                </div>
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                    <span class="px-4">Pending: <strong class="text-amber-500">{{ $analytics['pending'] }}</strong></span>
                    <span class="px-4">Updated: <strong class="text-emerald-600">{{ $analytics['updated'] }}</strong></span>
                    <span class="pl-4">This Month: <strong class="text-blue-600">{{ $analytics['this_month'] }}</strong></span>
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                <span>Pending: <strong class="text-amber-500">{{ $analytics['pending'] }}</strong></span>
                <span>Updated: <strong class="text-emerald-600">{{ $analytics['updated'] }}</strong></span>
                <span>This Month: <strong class="text-blue-600">{{ $analytics['this_month'] }}</strong></span>
            </div>
        </div>

        {{-- ── Filter bar (FAQ-style sub-header) ── --}}
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email, mobile…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-full focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <select wire:model.live="statusFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
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
    </div>

    {{-- ══════════════════ BODY — Applications list ══════════════════ --}}
    {{-- In the Website Enquiries' table look: S.No, the application (its icon,
         the applicant, under it the message on one line cut short with …,
         then, small, the qualification, how to reach them, when, the status
         and a document) and the actions. The rest is on its View; a click on
         the row opens it. --}}
    @php
        $statusText = [
            'new'       => 'text-amber-600',
            'contacted' => 'text-blue-600',
            'approved'  => 'text-emerald-600',
            'rejected'  => 'text-red-600',
        ];
    @endphp
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
                                <tr wire:key="app-{{ $app->id }}" wire:click="viewApplication({{ $app->id }})"
                                    class="hover:bg-gray-50/70 transition-colors cursor-pointer">

                                    {{-- S.No --}}
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-500 font-medium">{{ $applications->firstItem() + $index }}</span>
                                    </td>

                                    {{-- Application --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $app->full_name ?: 'Anonymous' }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $app->description ?: '—' }}</p>
                                                <p class="text-[11px] text-gray-400 truncate mt-0.5">
                                                    @if ($app->qualification)
                                                        {{ $app->qualification }}<span class="mx-1 text-gray-300">·</span>
                                                    @endif
                                                    @if ($app->email)
                                                        {{ $app->email }}<span class="mx-1 text-gray-300">·</span>
                                                    @endif
                                                    @if ($app->mobile)
                                                        {{ $app->mobile }}<span class="mx-1 text-gray-300">·</span>
                                                    @endif
                                                    {{ $app->created_at->format('D, d M Y · g:i A') }}
                                                    <span class="mx-1 text-gray-300">·</span>
                                                    <span class="{{ $statusText[$app->status] ?? 'text-gray-500' }}">{{ ucfirst($app->status ?: 'new') }}</span>
                                                    @if ($app->document_path)
                                                        <span class="mx-1 text-gray-300">·</span>
                                                        Document
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
                                                <button wire:click="viewDocument({{ $app->id }})" title="View Document"
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-6a3 3 0 11-3 3" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-gray-800">No applications found</p>
                <p class="text-sm text-gray-400 mt-1">Partner applications submitted from the website will appear here.</p>
            </div>
        @endif

        @if ($applications->hasPages())
            <div class="mt-6">{{ $applications->links() }}</div>
        @endif
    </div>

    {{-- ══════════════════ VIEW SLIDE-IN PANEL ══════════════════ --}}
    {{-- In the Website Enquiry view's look: the applicant in the header, then
         plain label / value rows; the document, then the status and remark
         (same fields, same Save Status) under a line. --}}
    @if ($viewing)
        @php $evStatus = $viewing->status ?: 'new'; @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeApplication"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewing->full_name ?: 'Application Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">Partner / Executive application · {{ ucfirst($evStatus) }}</p>
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
                        'Email'         => $viewing->email ?: '—',
                        'Mobile'        => $viewing->mobile ?: '—',
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
                        <span class="col-span-2 font-medium {{ $statusText[$evStatus] ?? 'text-gray-500' }}">{{ ucfirst($evStatus) }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Address</span>
                        <span class="col-span-2 text-gray-800 whitespace-pre-line break-words">{{ $viewing->address ?: '—' }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">About / Message</span>
                        <span class="col-span-2 text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $viewing->description ?: '—' }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Document</span>
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

                    {{-- Status + remark, under a line --}}
                    <div class="pt-4 mt-2 border-t border-gray-100 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                            <select wire:model="editStatus"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @foreach ($statuses as $s)
                                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            @error('editStatus')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                            <textarea wire:model="editRemark" rows="4" placeholder="Add a note about this application or status…"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 resize-y focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            @error('editRemark')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" wire:click="closeApplication" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="saveStatus" wire:loading.attr="disabled" wire:target="saveStatus"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveStatus">Save Status</span>
                        <span wire:loading wire:target="saveStatus">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ DELETE CONFIRM ══════════════════ --}}
    @if ($pendingDelete !== null)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Application</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">Are you sure you want to delete this application? This also removes the attached document and cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteApplication"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Yes, Delete</button>
                    <button wire:click="cancelDeleteApplication"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
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
