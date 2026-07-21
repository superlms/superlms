<div class="min-h-screen bg-gray-50" x-data="{ showView: false, viewRow: {} }">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + actions)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            {{-- Title row: buttons stay pinned to the right and never wrap below --}}
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Ledger</h1>
                    <p class="text-sm text-gray-500 mt-0.5 truncate">Track every credit and expense — fees in, salaries out</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button wire:click="openCredit"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Credit</span>
                        <span class="sm:hidden">Credit</span>
                    </button>
                    <button wire:click="openExpense"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Expense</span>
                        <span class="sm:hidden">Expense</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Analytics strip — divided from the header, sits above the filters --}}
        <div class="border-t border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex items-center gap-x-4 gap-y-1 text-xs sm:text-sm text-gray-500 flex-wrap sm:divide-x sm:divide-gray-200">
                <span class="sm:pr-4">Net: <strong class="{{ $netBalance >= 0 ? 'text-emerald-600' : 'text-red-600' }}">₹{{ number_format($netBalance, 2) }}</strong></span>
                <span class="sm:px-4">Credit: <strong class="text-emerald-600">₹{{ number_format($periodCredit, 2) }}</strong></span>
                <span class="sm:px-4">Expense: <strong class="text-red-600">₹{{ number_format($periodExpense, 2) }}</strong></span>
                <span class="sm:pl-4">Closing: <strong class="text-blue-600">₹{{ number_format($closingBalance, 2) }}</strong></span>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Period:
                </div>

                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">From</label>
                    <input type="date" wire:model.live="startDate" max="{{ $endDate }}"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">To</label>
                    <input type="date" wire:model.live="endDate" min="{{ $startDate }}"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">Day</label>
                    <input type="date" wire:model.live="singleDate"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">Month</label>
                    <select wire:change="setMonth($event.target.value)"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select month</option>
                        @foreach ($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}"
                                @selected(!$isOverall && $startDate === \Carbon\Carbon::parse($opt['value'].'-01')->startOfMonth()->toDateString() && $endDate === \Carbon\Carbon::parse($opt['value'].'-01')->endOfMonth()->toDateString())>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button wire:click="thisMonth"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-md hover:bg-gray-100">
                    This Month
                </button>
                <button wire:click="overall"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border
                        {{ $isOverall ? 'text-white bg-blue-600 border-blue-600 hover:bg-blue-700' : 'text-gray-700 bg-white border-gray-200 hover:bg-gray-100' }}">
                    Overall
                </button>

                <a href="{{ $isOverall
                        ? route('admin.ledger.statement', ['organization' => auth()->user()->organization_id, 'overall' => 1])
                        : route('admin.ledger.statement', ['organization' => auth()->user()->organization_id, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                    target="_blank"
                    class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-blue-600 bg-white border border-blue-200 rounded-md hover:bg-blue-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export PDF
                </a>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">

        @if (session('ledger_msg'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-2.5">
                {{ session('ledger_msg') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Particulars</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">From</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">To</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mode</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Credit</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Expense</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($entries as $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    {{ $row['date']->format('d M Y') }}
                                    @if (!empty($row['time']))
                                        <span class="block text-[11px] text-gray-400">{{ $row['time'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-900">{{ $row['reason'] }}</p>
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded
                                            {{ $row['source'] === 'Salary' ? 'bg-orange-50 text-orange-600' : ($row['source'] === 'Manual' ? 'bg-purple-50 text-purple-600' : 'bg-blue-50 text-blue-600') }}">
                                            {{ $row['source'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $row['from'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $row['to'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $row['mode'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-emerald-600">
                                    {{ $row['type'] === 'credit' ? '₹' . number_format($row['amount'], 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-red-600">
                                    {{ $row['type'] === 'expense' ? '₹' . number_format($row['amount'], 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold {{ $row['balance'] >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                                    ₹{{ number_format($row['balance'], 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        {{-- View is available for every row (auto + manual) --}}
                                        <button title="View details"
                                            @click="viewRow = {
                                                date: @js($row['date']->format('d M Y')),
                                                time: @js($row['time'] ?? null),
                                                reason: @js($row['reason']),
                                                source: @js($row['source']),
                                                from: @js($row['from'] ?? '—'),
                                                to: @js($row['to'] ?? '—'),
                                                collectedBy: @js($row['collected_by'] ?? null),
                                                mode: @js($row['mode'] ?: '—'),
                                                type: @js($row['type']),
                                                amount: @js(number_format($row['amount'], 2)),
                                                balance: @js(number_format($row['balance'], 2)),
                                            }; showView = true"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        {{-- Edit & Delete only for manually-added entries --}}
                                        @if (!empty($row['manual_id']))
                                            <button wire:click="openEdit({{ $row['manual_id'] }})" title="Edit entry"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button wire:click="confirmDelete({{ $row['manual_id'] }})" title="Delete entry"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800">No transactions found</p>
                                    <p class="text-xs text-gray-400 mt-1">Add a credit or expense, or pick a different period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $entries->links('livewire::tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ADD CREDIT / EXPENSE SLIDE-IN PANEL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit' : 'Add' }} {{ $modalType === 'expense' ? 'Expense' : 'Credit' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $modalType === 'expense' ? 'Record money going out' : 'Record money coming in' }}
                        </p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="rounded-lg px-3.5 py-2.5 text-sm font-medium
                        {{ $modalType === 'expense' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                        This entry is recorded under <strong>{{ $modalType === 'expense' ? 'Expenses' : 'Credits' }}</strong>.
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mDate" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('mDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mAmount" type="number" step="0.01" min="0" placeholder="0.00"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('mAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">From</label>
                            <input wire:model.defer="mParty" type="text"
                                placeholder="{{ $modalType === 'expense' ? 'Paid from (source / account)' : 'Received from (payer)' }}"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('mParty')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mode</label>
                            <select wire:model.defer="mMode"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @foreach ($modes as $mode)
                                    <option value="{{ $mode }}">{{ $mode }}</option>
                                @endforeach
                            </select>
                            @error('mMode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @if ($modalType === 'credit')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected by</label>
                            <input wire:model.defer="mCollectedBy" type="text" placeholder="Staff member who collected the money"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('mCollectedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">To</label>
                            <input wire:model.defer="mPartyTo" type="text" placeholder="Paid to (payee / vendor)"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('mPartyTo')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="mReason" rows="3" placeholder="What is this for?"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('mReason')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveManual" wire:loading.attr="disabled" wire:target="saveManual"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveManual">{{ $editingId ? 'Update' : 'Save' }} {{ $modalType === 'expense' ? 'Expense' : 'Credit' }}</span>
                        <span wire:loading wire:target="saveManual">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE CONFIRM OVERLAY --}}
    @if ($showDeleteConfirm)
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
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this entry?</h3>
                        <p class="text-sm text-gray-500">
                            This removes the manual ledger entry. Automatic fee &amp; salary rows can't be deleted here.
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="deleteManual" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="deleteManual">Delete</span>
                        <span wire:loading wire:target="deleteManual">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- VIEW TRANSACTION SLIDE-IN PANEL (client-side, works for every row) --}}
    <div x-cloak x-show="showView" class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" @click="showView = false"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">Transaction Details</h2>
                    <p class="text-xs text-gray-500 mt-0.5 truncate" x-text="viewRow.reason"></p>
                </div>
                <button @click="showView = false" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-3">
                <div class="rounded-lg px-3.5 py-3 border"
                    :class="viewRow.type === 'expense' ? 'bg-red-50 border-red-100' : 'bg-emerald-50 border-emerald-100'">
                    <p class="text-xs uppercase tracking-wider mb-0.5"
                        :class="viewRow.type === 'expense' ? 'text-red-400' : 'text-emerald-500'"
                        x-text="viewRow.type === 'expense' ? 'Expense' : 'Credit'"></p>
                    <p class="text-xl font-bold"
                        :class="viewRow.type === 'expense' ? 'text-red-600' : 'text-emerald-600'">
                        ₹<span x-text="viewRow.amount"></span>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Date</p>
                        <p class="text-sm font-medium text-gray-800">
                            <span x-text="viewRow.date"></span>
                            <span class="text-gray-400" x-show="viewRow.time" x-text="viewRow.time ? '· ' + viewRow.time : ''"></span>
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Source</p>
                        <p class="text-sm font-medium text-gray-800" x-text="viewRow.source"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">From</p>
                        <p class="text-sm font-medium text-gray-800" x-text="viewRow.from"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">To</p>
                        <p class="text-sm font-medium text-gray-800" x-text="viewRow.to"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100" x-show="viewRow.collectedBy">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Collected by</p>
                        <p class="text-sm font-medium text-gray-800" x-text="viewRow.collectedBy"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Mode</p>
                        <p class="text-sm font-medium text-gray-800" x-text="viewRow.mode"></p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Particulars</p>
                    <p class="text-sm font-medium text-gray-800" x-text="viewRow.reason"></p>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Running balance</p>
                    <p class="text-sm font-semibold text-blue-600">₹<span x-text="viewRow.balance"></span></p>
                </div>
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button @click="showView = false" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Close</button>
            </div>
        </div>
    </div>
</div>
