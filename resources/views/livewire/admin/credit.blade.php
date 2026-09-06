<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Credit</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Total: <strong class="text-gray-800">{{ $stats['total'] }}</strong></span>
                    <span class="px-4">Pending: <strong class="text-amber-500">{{ $stats['pending'] }}</strong></span>
                    <span class="px-4">Processing: <strong class="text-blue-600">{{ $stats['processing'] }}</strong></span>
                    <span class="px-4">Approved: <strong class="text-emerald-600">{{ $stats['approved'] }}</strong></span>
                    <span class="pl-4">Denied: <strong class="text-red-500">{{ $stats['denied'] }}</strong></span>
                </div>
                <button wire:click="openAskCreditModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ask Credit
                </button>
            </div>
        </div>
        <div class="flex lg:hidden flex-wrap gap-3 text-xs text-gray-500 mt-3">
            <span>Total: <strong class="text-gray-800">{{ $stats['total'] }}</strong></span>
            <span>Pending: <strong class="text-amber-500">{{ $stats['pending'] }}</strong></span>
            <span>Approved: <strong class="text-emerald-600">{{ $stats['approved'] }}</strong></span>
            <span>Denied: <strong class="text-red-500">{{ $stats['denied'] }}</strong></span>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

            {{-- ── Tab Bar ── --}}
            <div class="flex border-b border-gray-200">
                @foreach(['queries' => 'My Queries', 'policies' => 'Policies'] as $tab => $label)
                    <button wire:click="setTab('{{ $tab }}')"
                        class="px-6 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2
                            {{ $activeTab === $tab
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- ════════ QUERIES TAB ════════ --}}
            @if ($activeTab === 'queries')
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Heading</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Period</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Actions</th>
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
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $queries->firstItem() + $loop->index }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-800">{{ $query->heading }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $query->reason }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                        ₹{{ number_format($query->amount, 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ $query->start_date->format('d M Y') }}<br>
                                        <span class="text-gray-400">to</span> {{ $query->end_date->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium border {{ $sc }}">
                                            {{ ucfirst($query->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $query->created_at->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <button wire:click="viewQuery({{ $query->id }})" title="View"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            @if($query->status === 'pending')
                                                <button wire:click="openEditModal({{ $query->id }})" title="Edit"
                                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button wire:click="confirmDelete({{ $query->id }})" title="Delete"
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-400">No credit queries found</p>
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

            {{-- ════════ POLICIES TAB ════════ --}}
            @if ($activeTab === 'policies')
                <div class="p-5 space-y-4">
                    @forelse ($policies as $policy)
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">{{ $policy->title }}</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Updated {{ $policy->updated_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if ($policy->link)
                                        <a href="{{ $policy->link }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs px-2.5 py-1 text-blue-600
                                                   border border-blue-100 rounded-lg hover:bg-blue-50 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>Link
                                        </a>
                                    @endif
                                    @if ($policy->document)
                                        <a href="{{ Storage::disk('s3')->url($policy->document) }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs px-2.5 py-1 text-red-600
                                                   border border-red-100 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>PDF
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @if ($policy->image)
                                <div class="px-5 pt-4">
                                    <img src="{{ Storage::disk('s3')->url($policy->image) }}"
                                        alt="{{ $policy->title }}"
                                        class="w-full max-h-48 object-cover rounded-lg border border-gray-100">
                                </div>
                            @endif
                            <div class="px-5 py-4 text-sm text-gray-700 leading-relaxed space-y-3">
                                @forelse ($policy->body_paragraphs as $para)
                                    <p class="whitespace-pre-line">{{ $para }}</p>
                                @empty
                                    <p class="text-gray-400">No content.</p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="py-16 text-center">
                            <p class="text-sm text-gray-400">No policies added yet</p>
                        </div>
                    @endforelse
                </div>
            @endif

        </div>
    </div>

    {{-- ══════════ VIEW QUERY SLIDE-IN PANEL ══════════ --}}
    @if ($showViewModal && $selectedQuery)
        @php
            $q  = $selectedQuery;
            $rp = $q->repayment();
            $org = auth()->user()->organization;

            // Same label/value layout the Exams view panel uses.
            $rows = [
                'Status'    => ucfirst($q->status),
                'Heading'   => $q->heading,
                'Amount'    => '₹' . number_format($q->amount, 2),
                'Start Date' => $q->start_date?->format('d M Y') ?? '—',
                'Last Date'  => $q->end_date?->format('d M Y') ?? '—',
                'Requested' => $q->created_at->format('d M Y, g:i A'),
            ];

            if ($q->status === 'approved') {
                $rows['Approved On'] = $q->approved_at?->format('d M Y') ?? '—';
            }
        @endphp
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">Credit Query Details</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ ucfirst($q->status) }} · {{ $q->created_at->format('d M Y') }}</p>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">

                    {{-- ── What is owed, once the credit is approved ── --}}
                    @if ($q->status === 'approved')
                        <div class="rounded-xl border px-4 py-3.5
                            {{ $rp['settled']
                                ? 'border-gray-200 bg-gray-50'
                                : ($rp['is_overdue'] ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50') }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider
                                        {{ $rp['settled'] ? 'text-gray-500' : ($rp['is_overdue'] ? 'text-red-600' : 'text-emerald-700') }}">
                                        {{ $rp['settled'] ? 'Settled — total paid' : 'Payable till date' }}
                                    </p>
                                    <p class="text-2xl font-bold mt-0.5
                                        {{ $rp['settled'] ? 'text-gray-700' : ($rp['is_overdue'] ? 'text-red-700' : 'text-emerald-700') }}">
                                        ₹{{ number_format($rp['total'], 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        ₹{{ number_format($rp['principal'], 2) }} credit
                                        @if ($rp['penalty'] > 0)
                                            + ₹{{ number_format($rp['penalty'], 2) }} penalty
                                        @endif
                                    </p>
                                </div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide flex-shrink-0
                                    {{ $rp['settled']
                                        ? 'bg-gray-200 text-gray-600'
                                        : ($rp['is_overdue'] ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $rp['settled'] ? 'Paid' : ($rp['is_overdue'] ? 'Overdue' : 'On time') }}
                                </span>
                            </div>

                            <p class="text-xs mt-2.5 {{ $rp['is_overdue'] && !$rp['settled'] ? 'text-red-700' : 'text-gray-500' }}">
                                @if ($rp['settled'])
                                    Collected on {{ $q->collected_at->format('d M Y') }} — the last date was
                                    {{ $rp['due_date']?->format('d M Y') ?? '—' }}.
                                @elseif ($rp['is_overdue'])
                                    Last date was <strong>{{ $rp['due_date']?->format('d M Y') ?? '—' }}</strong> —
                                    {{ $rp['days_overdue'] }} day{{ $rp['days_overdue'] === 1 ? '' : 's' }} overdue.
                                    Pay as soon as possible; the penalty keeps growing each day.
                                @elseif ($rp['due_date'])
                                    Pay by <strong>{{ $rp['due_date']->format('d M Y') }}</strong> —
                                    {{ $rp['days_left'] }} day{{ $rp['days_left'] === 1 ? '' : 's' }} left.
                                @else
                                    No last date is set on this credit.
                                @endif
                            </p>
                        </div>

                        {{-- The arithmetic behind that figure --}}
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wider">Penalty / Day</p>
                                <p class="font-semibold text-gray-800">₹{{ number_format($rp['per_day'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wider">Days Overdue</p>
                                <p class="font-semibold {{ $rp['days_overdue'] > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $rp['days_overdue'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wider">Penalty So Far</p>
                                <p class="font-semibold {{ $rp['penalty'] > 0 ? 'text-red-600' : 'text-gray-800' }}">₹{{ number_format($rp['penalty'], 2) }}</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>
                    @endif

                    {{-- ── The request itself ── --}}
                    @foreach ($rows as $label => $value)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                            <span class="col-span-2 text-gray-800 font-medium break-words">{{ $value }}</span>
                        </div>
                    @endforeach

                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Reason</span>
                        <span class="col-span-2 text-gray-800 font-medium whitespace-pre-line leading-relaxed">{{ $q->reason ?: '—' }}</span>
                    </div>

                    @if ($q->admin_remark)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Admin Remark</span>
                            <span class="col-span-2 text-gray-800 font-medium whitespace-pre-line leading-relaxed">{{ $q->admin_remark }}</span>
                        </div>
                    @endif

                    @if ($q->status === 'approved' && $org && $org->bank_name)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Bank on File</span>
                            <span class="col-span-2 text-gray-800">
                                <span class="block font-medium">{{ $org->bank_name }}</span>
                                <span class="block font-mono text-xs text-gray-600 mt-0.5">
                                    {{ $org->bank_account_no ?: '—' }} · {{ $org->bank_ifsc ?: '—' }}
                                </span>
                                <span class="block text-xs text-gray-500 mt-0.5">
                                    {{ $org->bank_branch ?: '—' }}{{ $org->bank_holder_name ? ' · ' . $org->bank_holder_name : '' }}
                                </span>
                            </span>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ ASK / EDIT CREDIT SLIDE-IN PANEL ══════════ --}}
    @if ($showCreditModal)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCreditModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editQueryId ? 'Edit Credit Query' : 'Ask for Credit' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editQueryId ? 'Update your pending credit request' : 'Submit a new credit request' }}</p>
                    </div>
                    <button wire:click="closeCreditModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount Required (₹) <span class="text-red-500">*</span></label>
                        <input wire:model="creditAmount" type="number" min="1" step="1" placeholder="e.g. 50000"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500"/>
                        @error('creditAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date <span class="text-red-500">*</span></label>
                            <input wire:model.live="creditStartDate" type="date"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500"/>
                            @error('creditStartDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date <span class="text-red-500">*</span></label>
                            <input wire:model="creditEndDate" type="date"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500"/>
                            @error('creditEndDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Heading <span class="text-red-500">*</span></label>
                        <input wire:model="creditHeading" type="text" maxlength="255" placeholder="Brief heading for this credit request"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500"/>
                        @error('creditHeading') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Reason for Credit <span class="text-red-500">*</span></label>
                        <textarea wire:model="creditReason" rows="4" maxlength="2000" placeholder="Explain why you need this credit..."
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('creditReason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeCreditModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveCreditQuery" wire:loading.attr="disabled" wire:target="saveCreditQuery"
                        class="px-5 py-2 text-sm font-semibold bg-gray-900 hover:bg-gray-800 text-white rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveCreditQuery">{{ $editQueryId ? 'Update Query' : 'Submit Request' }}</span>
                        <span wire:loading wire:target="saveCreditQuery">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($pendingDeleteId)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Delete Credit Query?</h3>
                <p class="text-sm text-gray-500 mb-6">This action cannot be undone.</p>
                <div class="flex gap-3">
                    <button wire:click="cancelDelete"
                        class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="executeDelete"
                        class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
