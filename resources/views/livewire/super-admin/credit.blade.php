<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5 sticky top-0 z-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Credit Management</h1>
                <p class="text-sm text-gray-500 mt-0.5">Review school credit requests and manage policies</p>
            </div>
            {{-- Desktop analytics --}}
            <div class="hidden xl:flex items-center gap-3 text-sm text-gray-500 divide-x divide-gray-200">
                <span class="pr-3">Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                <span class="px-3">Pending: <strong class="text-amber-500">{{ $analytics['pending'] }}</strong></span>
                <span class="px-3">Processing: <strong class="text-blue-600">{{ $analytics['processing'] }}</strong></span>
                <span class="px-3">Approved: <strong class="text-emerald-600">{{ $analytics['approved'] }}</strong></span>
                <span class="px-3">Denied: <strong class="text-red-500">{{ $analytics['denied'] }}</strong></span>
                <span class="px-3">Active: <strong class="text-indigo-600">{{ $analytics['active_credits'] }}</strong></span>
                <span class="pl-3">Leased: <strong class="text-gray-800">₹{{ number_format($analytics['total_amount_leased'], 0) }}</strong></span>
            </div>
        </div>

        {{-- Mobile Stats --}}
        <div class="flex xl:hidden flex-wrap gap-3 text-xs text-gray-500 mt-3">
            <span>Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
            <span>Pending: <strong class="text-amber-500">{{ $analytics['pending'] }}</strong></span>
            <span>Processing: <strong class="text-blue-600">{{ $analytics['processing'] }}</strong></span>
            <span>Approved: <strong class="text-emerald-600">{{ $analytics['approved'] }}</strong></span>
            <span>Denied: <strong class="text-red-500">{{ $analytics['denied'] }}</strong></span>
            <span>Active: <strong class="text-indigo-600">{{ $analytics['active_credits'] }}</strong></span>
            <span>Leased: <strong class="text-gray-800">₹{{ number_format($analytics['total_amount_leased'], 0) }}</strong></span>
        </div>

        {{-- Filters (credit tab only) --}}
        @if ($activeTab === 'credit')
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[200px] max-w-xs">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            placeholder="Search school name, email..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500"/>
                    </div>
                    <select wire:model.live="statusFilter"
                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="approved">Approved</option>
                        <option value="denied">Denied</option>
                    </select>
                    <select wire:model.live="orgFilter"
                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="">All Schools</option>
                        @foreach ($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                    @if ($search || $statusFilter || $orgFilter)
                        <button wire:click="clearFilters" title="Clear filters"
                            class="px-3 py-2 text-sm text-red-600 border border-red-200 bg-red-50
                                   hover:bg-red-100 rounded-lg transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="p-4 sm:p-6">

        {{-- ── TABS ── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex border-b border-gray-200">
                @foreach([
                    'credit'        => 'Credit Queries',
                    'view_policies' => 'View Policies',
                    'edit_policies' => 'Edit Policies',
                ] as $tab => $label)
                    <button wire:click="setTab('{{ $tab }}')"
                        class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2
                            {{ $activeTab === $tab
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- ════════ CREDIT QUERIES TAB ════════ --}}
            @if ($activeTab === 'credit')
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">School</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Heading / Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($queries as $query)
                                @php
                                    $sc = match($query->status) {
                                        'approved'   => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'denied'     => 'bg-red-50 text-red-700 border-red-100',
                                        'processing' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        default      => 'bg-amber-50 text-amber-700 border-amber-100',
                                    };
                                    $org = $query->organization;
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors">

                                    {{-- School --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            @if ($org->logo)
                                                <img src="{{ $org->logo }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-indigo-600">{{ strtoupper(substr($org->name,0,1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-gray-800 truncate max-w-[120px]">{{ $org->name }}</p>
                                                <p class="text-[10px] text-gray-400 truncate max-w-[120px]">{{ $org->email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Heading / Reason --}}
                                    <td class="px-4 py-3 max-w-[180px]">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $query->heading }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ $query->reason }}</p>
                                    </td>

                                    {{-- Amount --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm font-semibold text-gray-800">₹{{ number_format($query->amount, 0) }}</p>
                                        @if ($query->collected_at)
                                            <span class="inline-flex items-center gap-0.5 text-[10px] text-teal-600 font-semibold mt-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Collected
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Period --}}
                                    <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                        {{ $query->start_date->format('d M Y') }}<br>
                                        <span class="text-gray-400">to</span> {{ $query->end_date->format('d M Y') }}
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium border {{ $sc }}">
                                            {{ ucfirst($query->status) }}
                                        </span>
                                    </td>

                                    {{-- Date --}}
                                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $query->created_at->format('d M Y') }}
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            {{-- View --}}
                                            <button wire:click="viewQuery({{ $query->id }})" title="View"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            {{-- Update Status --}}
                                            <button wire:click="openStatusModal({{ $query->id }})" title="Update Status"
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                            {{-- Approve shortcut --}}
                                            @if ($query->status !== 'approved')
                                                <button wire:click="openApproveModal({{ $query->id }})" title="Approve"
                                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            @endif
                                            {{-- Mark Collected (approved + not yet collected) --}}
                                            @if ($query->status === 'approved' && !$query->collected_at)
                                                <button wire:click="openCollectModal({{ $query->id }})" title="Mark as Collected"
                                                    class="p-1.5 text-teal-600 hover:bg-teal-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                            {{-- Delete --}}
                                            <button wire:click="confirmDeleteQuery({{ $query->id }})" title="Delete"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-14 text-center">
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-400">No credit queries found</p>
                                        @if ($search || $statusFilter || $orgFilter)
                                            <button wire:click="clearFilters"
                                                class="mt-2 text-sm text-blue-600 hover:underline">Clear filters</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($queries->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">{{ $queries->links() }}</div>
                @endif
            @endif

            {{-- ════════ VIEW POLICIES TAB ════════ --}}
            @if ($activeTab === 'view_policies')
                <div class="p-5 space-y-4">
                    @forelse ($policies as $policy)
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-semibold text-gray-800">{{ $policy->title }}</h3>
                                        @if (!$policy->is_active)
                                            <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full">Inactive</span>
                                        @else
                                            <span class="text-xs px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-full">Active</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">Updated {{ $policy->updated_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if ($policy->link)
                                        <a href="{{ $policy->link }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs px-2.5 py-1 text-blue-600 border border-blue-100 rounded-lg hover:bg-blue-50">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>Link
                                        </a>
                                    @endif
                                    @if ($policy->document)
                                        <a href="{{ Storage::disk('s3')->url($policy->document) }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs px-2.5 py-1 text-red-600 border border-red-100 rounded-lg hover:bg-red-50">
                                            PDF
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @if ($policy->image)
                                <div class="px-5 pt-4">
                                    <img src="{{ Storage::disk('s3')->url($policy->image) }}" alt="{{ $policy->title }}"
                                        class="w-full max-h-48 object-cover rounded-lg border border-gray-100">
                                </div>
                            @endif
                            <div class="px-5 py-4 text-sm text-gray-700 leading-relaxed">
                                {!! nl2br(e($policy->content)) !!}
                            </div>
                        </div>
                    @empty
                        <div class="py-16 text-center">
                            <p class="text-sm text-gray-400">No policies created yet</p>
                        </div>
                    @endforelse
                    @if ($policies->hasPages())
                        <div class="pt-4">{{ $policies->links() }}</div>
                    @endif
                </div>
            @endif

            {{-- ════════ EDIT POLICIES TAB ════════ --}}
            @if ($activeTab === 'edit_policies')
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Manage Policies</h3>
                        @if ($policies->total() === 0)
                            <button wire:click="openPolicyForm()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700
                                       text-white text-sm font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Policy
                            </button>
                        @endif
                    </div>

                    <div class="space-y-3">
                        @forelse ($policies as $policy)
                            <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 flex items-center gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-semibold text-gray-800 truncate">{{ $policy->title }}</h4>
                                        @if (!$policy->is_active)
                                            <span class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded-full flex-shrink-0">Inactive</span>
                                        @else
                                            <span class="text-[10px] px-1.5 py-0.5 bg-emerald-50 text-emerald-600 rounded-full flex-shrink-0">Active</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ Str::limit($policy->content, 80) }}</p>
                                    <div class="flex items-center gap-3 mt-1">
                                        @if ($policy->image)    <span class="text-[10px] text-blue-500">Image</span> @endif
                                        @if ($policy->document) <span class="text-[10px] text-red-500">PDF</span> @endif
                                        @if ($policy->link)     <span class="text-[10px] text-indigo-500">Link</span> @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button wire:click="openPolicyForm({{ $policy->id }})" title="Edit"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDeletePolicy({{ $policy->id }})" title="Delete"
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <p class="text-sm text-gray-400">No policies yet. Click "Add Policy" to create one.</p>
                            </div>
                        @endforelse
                    </div>
                    @if ($policies->hasPages())
                        <div class="mt-4">{{ $policies->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ VIEW QUERY PANEL ══════════ --}}
    @if ($showViewModal && $selectedQuery)
        <div class="fixed inset-0 z-[9999] flex items-start justify-end bg-black/30 backdrop-blur-sm"
            wire:click.self="closeViewModal">
            <div class="relative w-full max-w-xl h-screen bg-white shadow-2xl flex flex-col"
                x-data x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                {{-- Sticky Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-gray-900">Credit Query</h2>
                            <p class="text-xs text-gray-400 truncate">{{ $selectedQuery->organization->name }}</p>
                        </div>
                        @php
                            $sc2 = match($selectedQuery->status) {
                                'approved'   => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'denied'     => 'bg-red-100 text-red-700 border-red-200',
                                'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                default      => 'bg-amber-100 text-amber-700 border-amber-200',
                            };
                        @endphp
                        <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full font-semibold border {{ $sc2 }} flex-shrink-0">
                            {{ ucfirst($selectedQuery->status) }}
                        </span>
                        @if ($selectedQuery->collected_at)
                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-semibold bg-teal-100 text-teal-700 border border-teal-200 flex-shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Collected
                            </span>
                        @endif
                    </div>
                    <button wire:click="closeViewModal"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0 ml-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    @php $q = $selectedQuery; $org = $q->organization; @endphp

                    {{-- School Card --}}
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        @if ($org->logo)
                            <img src="{{ $org->logo }}" class="w-14 h-14 rounded-2xl object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-14 h-14 rounded-2xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-xl font-bold text-indigo-600">{{ strtoupper(substr($org->name,0,1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-base font-bold text-gray-900 truncate">{{ $org->name }}</p>
                            <p class="text-sm text-gray-500 truncate">{{ $org->email }}</p>
                            <p class="text-xs text-gray-400">{{ $org->mobile_number ?? '' }}</p>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                                <span>Students: <strong class="text-gray-600">{{ $org->total_students ?? 0 }}</strong></span>
                                <span>Teachers: <strong class="text-gray-600">{{ $org->total_teachers ?? 0 }}</strong></span>
                            </div>
                        </div>
                    </div>

                    {{-- Date + Period Grid --}}
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                            <p class="text-gray-400 font-medium mb-1">Submitted</p>
                            <p class="text-gray-800 font-semibold">{{ $q->created_at->format('d M Y') }}</p>
                            <p class="text-gray-500">{{ $q->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                            <p class="text-gray-400 font-medium mb-1">Period</p>
                            <p class="text-gray-800 font-semibold">{{ $q->start_date->format('d M Y') }}</p>
                            <p class="text-gray-500">to {{ $q->end_date->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Heading & Reason --}}
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <p class="text-xs text-blue-400 font-semibold uppercase tracking-wide mb-1">Heading</p>
                        <p class="text-sm font-bold text-blue-900 mb-3">{{ $q->heading }}</p>
                        <p class="text-xs text-blue-400 font-semibold uppercase tracking-wide mb-1">Reason</p>
                        <p class="text-sm text-blue-800 whitespace-pre-line leading-relaxed">{{ $q->reason }}</p>
                    </div>

                    {{-- Amount --}}
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
                        <p class="text-xs text-indigo-400 font-semibold uppercase tracking-wide mb-1">Amount Requested</p>
                        <p class="text-3xl font-bold text-indigo-700">₹{{ number_format($q->amount, 0) }}</p>
                        @if ($q->collected_at)
                            <p class="text-xs text-teal-600 mt-1.5 font-semibold">
                                ✓ Collected on {{ $q->collected_at->format('d M Y') }}
                            </p>
                        @endif
                    </div>

                    {{-- Approval Details --}}
                    @if ($q->status === 'approved')
                        @php
                            $lateDays     = $q->end_date->isPast() ? max(0, $q->end_date->diffInDays(now())) : 0;
                            $totalPenalty = ($q->penalties_per_day ?? 0) * $lateDays;
                            $totalReceive = $q->amount + $totalPenalty;
                        @endphp
                        <div class="border border-emerald-200 bg-emerald-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-3">Approval Details</p>
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <div>Penalties/Day: <strong class="text-gray-800">₹{{ number_format($q->penalties_per_day ?? 0, 0) }}</strong></div>
                                <div>Late Days: <strong class="text-red-600">{{ $lateDays }} {{ $lateDays === 1 ? 'day' : 'days' }}</strong></div>
                                <div>Total Penalty: <strong class="text-red-600">₹{{ number_format($totalPenalty, 0) }}</strong></div>
                                <div>Amount Credit: <strong class="text-gray-800">₹{{ number_format($q->amount, 0) }}</strong></div>
                                <div>Total to Receive: <strong class="text-emerald-700">₹{{ number_format($totalReceive, 0) }}</strong></div>
                                <div>Approved on: <strong class="text-gray-800">{{ $q->approved_at?->format('d M Y') ?? '—' }}</strong></div>
                            </div>
                        </div>

                        {{-- School Bank Details --}}
                        @if ($org->bank_name)
                            <div class="border border-gray-200 rounded-xl p-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">School Bank Details</p>
                                <div class="space-y-2">
                                    @foreach([
                                        'Bank'   => $org->bank_name,
                                        'Acc No' => $org->bank_account_no,
                                        'IFSC'   => $org->bank_ifsc,
                                        'Branch' => $org->bank_branch,
                                        'Holder' => $org->bank_holder_name,
                                    ] as $lbl => $val)
                                        <div class="flex gap-4">
                                            <span class="text-xs text-gray-400 w-16 flex-shrink-0">{{ $lbl }}</span>
                                            <span class="text-sm font-semibold font-mono text-gray-800">{{ $val ?? '—' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Admin Remark --}}
                    @if ($q->admin_remark)
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4">
                            <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">Remark</p>
                            <p class="text-sm text-gray-700">{{ $q->admin_remark }}</p>
                        </div>
                    @endif
                </div>

                {{-- Sticky Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-white flex gap-3 flex-shrink-0">
                    @if ($selectedQuery->status !== 'approved')
                        <button wire:click="openApproveModal({{ $selectedQuery->id }})"
                            class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Approve
                        </button>
                    @elseif (!$selectedQuery->collected_at)
                        <button wire:click="openCollectModal({{ $selectedQuery->id }})"
                            class="flex-1 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Mark Collected
                        </button>
                    @endif
                    <button wire:click="closeViewModal"
                        class="px-5 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors flex-shrink-0">
                        Close
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ APPROVE PANEL ══════════ --}}
    @if ($showApproveModal)
        <div class="fixed inset-0 z-[9999] flex items-start justify-end bg-black/30 backdrop-blur-sm"
            wire:click.self="closeApproveModal">
            <div class="relative w-full max-w-lg h-screen bg-white shadow-2xl flex flex-col"
                x-data x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Approve Credit Query</h2>
                            <p class="text-xs text-gray-400">Fill in the approval details</p>
                        </div>
                    </div>
                    <button wire:click="closeApproveModal"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                            Amount (₹) <span class="text-red-400">*</span>
                        </label>
                        <input wire:model="approveAmount" type="number" min="1"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 transition-colors"/>
                        @error('approveAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                                Start Date <span class="text-red-400">*</span>
                            </label>
                            <input wire:model="approveStartDate" type="date"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"/>
                            @error('approveStartDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                                End Date <span class="text-red-400">*</span>
                            </label>
                            <input wire:model="approveEndDate" type="date"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"/>
                            @error('approveEndDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                            Penalties Per Day (₹)
                        </label>
                        <input wire:model="approvePenaltiesPerDay" type="number" min="0" step="0.01" placeholder="0"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                            Remark (optional)
                        </label>
                        <textarea wire:model="approveRemark" rows="5" placeholder="Add approval notes..."
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl resize-none
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-white flex gap-3 flex-shrink-0">
                    <button wire:click="approveQuery"
                        class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Pay Now &amp; Approve
                    </button>
                    <button wire:click="closeApproveModal"
                        class="px-5 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors flex-shrink-0">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ STATUS / REMARK PANEL ══════════ --}}
    @if ($showStatusModal)
        <div class="fixed inset-0 z-[9999] flex items-start justify-end bg-black/30 backdrop-blur-sm"
            wire:click.self="closeStatusModal">
            <div class="relative w-full max-w-md h-screen bg-white shadow-2xl flex flex-col"
                x-data x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Update Status</h2>
                            <p class="text-xs text-gray-400">Change query status &amp; add remark</p>
                        </div>
                    </div>
                    <button wire:click="closeStatusModal"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Status</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach([
                                'pending'    => ['amber',   'Pending'],
                                'processing' => ['blue',    'Processing'],
                                'approved'   => ['emerald', 'Approved'],
                                'denied'     => ['red',     'Denied'],
                            ] as $val => [$color, $label2])
                                <button wire:click="$set('newStatus', '{{ $val }}')"
                                    class="py-2.5 px-3 text-sm font-medium rounded-xl border-2 transition-colors
                                        {{ $newStatus === $val
                                            ? 'border-'.$color.'-500 bg-'.$color.'-50 text-'.$color.'-700'
                                            : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                    {{ $label2 }}
                                </button>
                            @endforeach
                        </div>
                        @error('newStatus') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Remark</label>
                        <textarea wire:model="statusRemark" rows="7" placeholder="Add a remark for the school..."
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl resize-none
                                   focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-white flex gap-3 flex-shrink-0">
                    <button wire:click="updateStatus"
                        class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
                        Save Status
                    </button>
                    <button wire:click="closeStatusModal"
                        class="px-5 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors flex-shrink-0">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ POLICY FORM PANEL ══════════ --}}
    @if ($showPolicyForm)
        <div class="fixed inset-0 z-[9999] flex items-start justify-end bg-black/30 backdrop-blur-sm"
            wire:click.self="closePolicyForm">
            <div class="relative w-full max-w-xl h-screen bg-white shadow-2xl flex flex-col"
                x-data x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">
                                {{ $editPolicyId ? 'Edit Policy' : 'Add New Policy' }}
                            </h2>
                            <p class="text-xs text-gray-400">Credit terms &amp; conditions</p>
                        </div>
                    </div>
                    <button wire:click="closePolicyForm"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                            Title <span class="text-red-400">*</span>
                        </label>
                        <input wire:model="policyTitle" type="text" placeholder="Policy title"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-blue-500 transition-colors"/>
                        @error('policyTitle') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                            Content <span class="text-red-400">*</span>
                        </label>
                        <textarea wire:model="policyContent" rows="9"
                            placeholder="Write policy content here (terms and conditions style)..."
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl resize-y
                                   focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('policyContent') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                            Reference Link (optional)
                        </label>
                        <input wire:model="policyLink" type="url" placeholder="https://..."
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500"/>
                        @error('policyLink') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                                Image (optional)
                            </label>
                            <input wire:model="policyImage" type="file" accept="image/*"
                                class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg
                                       file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700
                                       hover:file:bg-blue-100 border border-gray-300 rounded-xl"/>
                            @error('policyImage') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                                Document PDF (optional)
                            </label>
                            <input wire:model="policyDocument" type="file" accept=".pdf,.doc,.docx"
                                class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg
                                       file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700
                                       hover:file:bg-red-100 border border-gray-300 rounded-xl"/>
                            @error('policyDocument') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input wire:model="policyIsActive" type="checkbox"
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"/>
                        <span class="text-sm text-gray-700">Active (visible to schools)</span>
                    </label>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-white flex gap-3 flex-shrink-0">
                    <button wire:click="savePolicy"
                        class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                        {{ $editPolicyId ? 'Update Policy' : 'Create Policy' }}
                    </button>
                    <button wire:click="closePolicyForm"
                        class="px-5 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors flex-shrink-0">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ MARK AS COLLECTED CONFIRM ══════════ --}}
    @if ($showCollectModal)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
                x-data x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <div class="p-6 text-center">
                    <div class="w-14 h-14 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-2">Mark as Collected?</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Has <strong class="text-gray-700">₹{{ $collectQueryAmount }}</strong> from
                        <strong class="text-gray-700">{{ $collectQuerySchool }}</strong> been received?
                    </p>
                </div>
                <div class="flex border-t border-gray-100">
                    <button wire:click="closeCollectModal"
                        class="flex-1 py-3.5 text-sm font-medium text-gray-600 hover:bg-gray-50
                               transition-colors border-r border-gray-100">
                        Cancel
                    </button>
                    <button wire:click="markAsCollected"
                        class="flex-1 py-3.5 text-sm font-semibold text-teal-600 hover:bg-teal-50 transition-colors">
                        Yes, Collected
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE QUERY CONFIRM ══════════ --}}
    @if ($pendingDeleteQueryId)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Delete Credit Query?</h3>
                <p class="text-sm text-gray-500 mb-6">This cannot be undone.</p>
                <div class="flex gap-3">
                    <button wire:click="cancelDeleteQuery"
                        class="flex-1 px-4 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="executeDeleteQuery"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE POLICY CONFIRM ══════════ --}}
    @if ($pendingDeletePolicyId)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Delete Policy?</h3>
                <p class="text-sm text-gray-500 mb-6">This will permanently delete the policy and its files.</p>
                <div class="flex gap-3">
                    <button wire:click="cancelDeletePolicy"
                        class="flex-1 px-4 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="executeDeletePolicy"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
