<div class="min-h-screen bg-gray-50"
    x-data="{
        showView: false,
        viewRow: {},
        showExport: false,
        expMode: 'all',
        expFrom: @js($startDate ?: now()->startOfMonth()->toDateString()),
        expTo: @js($endDate ?: now()->toDateString()),
        expDay: @js(now()->toDateString()),
        exporting: false,
        canExport() {
            if (this.expMode === 'all') return true;
            if (this.expMode === 'day') return !! this.expDay;
            return !! (this.expFrom && this.expTo);
        },
        async download() {
            if (! this.canExport() || this.exporting) return;
            this.exporting = true;
            try {
                await this.$wire.exportStatement(this.expMode, this.expFrom, this.expTo, this.expDay);
            } finally {
                this.exporting = false;
                this.showExport = false;
            }
        },
    }">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- The school Ledger's screen (livewire/admin/ledger) for SuperLMS's own
         money: fees received from the schools and messaging wallet recharges
         come in on their own, salaries paid go out on their own, and credits
         and expenses made by hand can be added, edited and deleted. --}}
    @php
        $srcClass = [
            'School Fee'       => 'bg-blue-50 text-blue-600',
            'Messaging Wallet' => 'bg-violet-50 text-violet-600',
            'Salary'           => 'bg-orange-50 text-orange-600',
            'Fee Correction'   => 'bg-rose-50 text-rose-600',
            'Manual'           => 'bg-gray-100 text-gray-600',
        ];
        $inr = fn ($v) => '₹' . number_format((float) $v, 2);
        // The header's figures that open what they are made of (partials/ledger-figure):
        // Credit and Expense by source — a line filters the list — and Closing from
        // the opening balance.
        $bySource = function (string $type, array $sources, array $by, string $color) use ($srcClass, $inr, $typeFilter, $sourceFilter) {
            return array_map(fn ($src) => [
                'label' => $src, 'pill' => $srcClass[$src] ?? 'bg-gray-100 text-gray-600',
                'amount' => $inr($by[$src] ?? 0), 'amountClass' => ($by[$src] ?? 0) > 0 ? $color : 'text-gray-400',
                'type' => $type, 'source' => $src, 'on' => $typeFilter === $type && $sourceFilter === $src,
            ], $sources);
        };
        $plain = fn ($label, $amount, $class) => ['label' => $label, 'pill' => null, 'amount' => $amount, 'amountClass' => $class, 'type' => null, 'source' => null, 'on' => false];
        $figures = [
            'credit' => [
                'label' => 'Credit', 'value' => $inr($periodCredit), 'valueClass' => 'text-emerald-600', 'title' => 'Credit by source',
                'lines' => $bySource('credit', \App\Livewire\SuperAdmin\Ledger::CREDIT_SOURCES, $creditBySource, 'text-emerald-600'),
            ],
            'expense' => [
                'label' => 'Expense', 'value' => $inr($periodExpense), 'valueClass' => 'text-red-600', 'title' => 'Expense by source',
                'lines' => $bySource('expense', \App\Livewire\SuperAdmin\Ledger::EXPENSE_SOURCES, $expenseBySource, 'text-red-600'),
            ],
            'closing' => [
                'label' => 'Closing', 'value' => $inr($closingBalance), 'valueClass' => 'text-blue-600', 'title' => 'How the balance closes',
                'lines' => [
                    $plain('Opening balance', $inr($openingBalance), $openingBalance < 0 ? 'text-red-600' : 'text-gray-800'),
                    $plain('+ Credit', $inr($periodCredit), 'text-emerald-600'),
                    $plain('− Expense', $inr($periodExpense), 'text-red-600'),
                    $plain('Closing balance', $inr($closingBalance), 'text-blue-600'),
                ],
            ],
        ];
    @endphp

    {{-- ══════════ HEADER (analytics + actions, as the school Ledger has it) ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Ledger</h1>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- The school Ledger's four, following the period picked below;
                         Credit, Expense and Closing open what they are made of. --}}
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200 mr-1 whitespace-nowrap">
                        <span class="pr-4">Net: <strong class="{{ $periodNet >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $inr($periodNet) }}</strong></span>
                        @include('livewire.super-admin.partials.ledger-figure', $figures['credit'] + ['class' => 'px-4'])
                        @include('livewire.super-admin.partials.ledger-figure', $figures['expense'] + ['class' => 'px-4'])
                        @include('livewire.super-admin.partials.ledger-figure', $figures['closing'] + ['class' => 'pl-4'])
                    </div>

                    <button type="button" @click="showExport = true"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                    <button wire:click="openAdd"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span class="hidden sm:inline">Add</span>
                    </button>
                </div>
            </div>

            {{-- Mobile / tablet stats — inside the same block, as the school Ledger has them. --}}
            <div class="relative flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Net: <strong class="{{ $periodNet >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $inr($periodNet) }}</strong></span>
                @include('livewire.super-admin.partials.ledger-figure', $figures['credit'] + ['class' => ''])
                @include('livewire.super-admin.partials.ledger-figure', $figures['expense'] + ['class' => ''])
                @include('livewire.super-admin.partials.ledger-figure', $figures['closing'] + ['class' => ''])
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>

                <button wire:click="thisMonth"
                    class="px-2.5 py-1.5 text-xs font-medium rounded-md border {{ $isThisMonth ? 'text-white bg-blue-600 border-blue-600 hover:bg-blue-700' : 'text-gray-700 bg-white border-gray-200 hover:bg-gray-100' }}">
                    This Month
                </button>
                <button wire:click="overall"
                    class="px-2.5 py-1.5 text-xs font-medium rounded-md border {{ $isOverall ? 'text-white bg-blue-600 border-blue-600 hover:bg-blue-700' : 'text-gray-700 bg-white border-gray-200 hover:bg-gray-100' }}">
                    Overall
                </button>

                <span class="hidden sm:inline-block w-px h-5 bg-gray-200"></span>

                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">Start</label>
                    <input type="date" wire:model.live="startDate" max="{{ $endDate }}"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">End</label>
                    <input type="date" wire:model.live="endDate" min="{{ $startDate }}"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>

                <span class="hidden sm:inline-block w-px h-5 bg-gray-200"></span>

                <select wire:model.live="typeFilter" aria-label="Type"
                    class="text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700">
                    <option value="">Credit &amp; expense</option>
                    <option value="credit">Credits</option>
                    <option value="expense">Expenses</option>
                </select>
                <select wire:model.live="sourceFilter" aria-label="Source"
                    class="text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700">
                    <option value="">Every source</option>
                    @foreach (\App\Livewire\SuperAdmin\Ledger::SOURCES as $src)
                        <option value="{{ $src }}">{{ $src }}</option>
                    @endforeach
                </select>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search school, party, remark…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 w-48 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                @if (!$isThisMonth || $filtered)
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ BODY (the list alone, as the school Ledger has it) ══════════ --}}
    <div class="p-4 sm:p-6">

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full table-fixed">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="w-[10%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="w-[24%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Particulars</th>
                        <th class="w-[15%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">From</th>
                        <th class="w-[13%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">To</th>
                        <th class="w-[9%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mode</th>
                        <th class="w-[11%] px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="w-[10%] px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                        <th class="w-[8%] px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $row)
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="sa-ledger-{{ $row['source'] }}-{{ $row['manual_id'] ?? '' }}-{{ $loop->index }}-{{ $row['sort_at'] }}">
                            <td class="px-3 py-3 text-sm text-gray-600">
                                {{ $row['date']->format('d M Y') }}
                                @if (!empty($row['time']))
                                    <span class="block text-[11px] text-gray-400">{{ $row['time'] }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-start gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-gray-900 break-words">{{ $row['reason'] }}</p>
                                    @if ($row['source'] !== 'Manual')
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded flex-shrink-0 {{ $srcClass[$row['source']] ?? 'bg-gray-100 text-gray-600' }}">{{ $row['source'] }}</span>
                                    @endif
                                </div>
                                @if (!empty($row['receipt']))
                                    <p class="text-xs text-gray-400 break-words">{{ $row['receipt'] }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-600 break-words">{{ $row['from'] }}</td>
                            <td class="px-3 py-3 text-sm text-gray-600 break-words">{{ $row['to'] }}</td>
                            <td class="px-3 py-3 text-sm text-gray-500">
                                <div class="break-words">{{ $row['mode'] ?: '—' }}</div>
                                <span class="block text-[11px] text-gray-400">{{ empty($row['manual_id']) ? 'Auto' : 'Manual' }}</span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <span class="block text-sm font-semibold {{ $row['type'] === 'expense' ? 'text-red-600' : 'text-emerald-600' }}">{{ $inr($row['amount']) }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $row['type'] === 'expense' ? 'Expense' : 'Credit' }}</span>
                            </td>
                            <td class="px-3 py-3 text-right text-sm font-semibold {{ $row['balance'] >= 0 ? 'text-gray-800' : 'text-red-600' }}">{{ $inr($row['balance']) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button title="View details"
                                        @click="viewRow = {
                                            date: @js($row['date']->format('d M Y')),
                                            time: @js($row['time'] ?? null),
                                            reason: @js($row['reason']),
                                            source: @js($row['source']),
                                            from: @js($row['from']),
                                            to: @js($row['to']),
                                            collectedBy: @js($row['collected_by'] ?? null),
                                            receipt: @js($row['receipt'] ?? null),
                                            mode: @js($row['mode'] ?: '—'),
                                            type: @js($row['type']),
                                            amount: @js(number_format($row['amount'], 2)),
                                            balance: @js(number_format($row['balance'], 2)),
                                            manual: @js(!empty($row['manual_id'])),
                                        }; showView = true"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </button>
                                    @if (!empty($row['manual_id']))
                                        <button wire:click="openEdit({{ $row['manual_id'] }})" title="Edit entry"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button wire:click="deleteManual({{ $row['manual_id'] }})" wire:confirm="Delete this {{ $row['type'] === 'expense' ? 'expense' : 'credit' }} of {{ $inr($row['amount']) }}?" title="Delete entry"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-800">No transactions found</p>
                                <p class="text-xs text-gray-400 mt-1">Add a credit or expense, or pick a different period.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($entries->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $entries->links('livewire::tailwind') }}</div>
            @endif
        </div>
    </div>

    {{-- ══════════ ADD / EDIT SLIDE-IN ══════════ --}}
    @if ($showModal)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit' : 'Add' }} {{ $modalType === 'expense' ? 'Expense' : ($modalType === 'credit' ? 'Credit' : 'Entry') }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $modalType === 'expense' ? 'Money going out' : ($modalType === 'credit' ? 'Money coming in' : 'Choose what you are adding') }}</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">What is it? <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['credit' => 'Credit', 'expense' => 'Expense'] as $value => $label)
                                <button type="button" wire:click="chooseType('{{ $value }}')"
                                    class="px-3.5 py-3 text-left border-2 rounded-md transition-all {{ $modalType === $value ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:bg-gray-50' }}">
                                    <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if ($modalType === 'credit' || $modalType === 'expense')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                                <input wire:model="mDate" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('mDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                                <input wire:model="mAmount" type="number" step="0.01" min="0" placeholder="0.00" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('mAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">From <span class="text-red-500">*</span></label>
                                <input wire:model="mParty" type="text" placeholder="{{ $modalType === 'expense' ? 'Paid from (account)' : 'Received from (payer)' }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('mParty')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Mode <span class="text-red-500">*</span></label>
                                <select wire:model="mMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
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
                                <input wire:model="mCollectedBy" type="text" placeholder="Who received the money" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('mCollectedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">To <span class="text-red-500">*</span></label>
                                <input wire:model="mPartyTo" type="text" placeholder="Paid to (payee / vendor)" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('mPartyTo')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark <span class="text-red-500">*</span></label>
                            <textarea wire:model="mReason" rows="3" placeholder="What is this for?" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            @error('mReason')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    @if ($modalType === 'credit' || $modalType === 'expense')
                        <button wire:click="saveManual" wire:loading.attr="disabled" wire:target="saveManual"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveManual">{{ $editingId ? 'Update' : 'Save' }} {{ $modalType === 'expense' ? 'Expense' : 'Credit' }}</span>
                            <span wire:loading wire:target="saveManual">Saving…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ EXPORT POPUP ══════════ --}}
    <div x-cloak x-show="showExport" :class="showExport && 'lms-cover'"
        @click.self="exporting || (showExport = false)"
        @keydown.escape.window="exporting || (showExport = false)"
        class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
        <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Export Statement</h3>
                    <p class="text-xs text-gray-400">Choose the period the PDF should cover.</p>
                </div>
            </div>

            <div class="space-y-4 mb-5">
                <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg">
                    @foreach (['all' => 'All time', 'day' => 'Single day', 'range' => 'Date range'] as $m => $label)
                        <button type="button" @click="expMode = '{{ $m }}'" class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md"
                            :class="expMode === '{{ $m }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">{{ $label }}</button>
                    @endforeach
                </div>
                <div x-show="expMode === 'range'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">From</label>
                        <input type="date" x-model="expFrom" :max="expTo" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">To</label>
                        <input type="date" x-model="expTo" :min="expFrom" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>
                </div>
                <div x-show="expMode === 'day'">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Date</label>
                    <input type="date" x-model="expDay" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <p x-show="expMode === 'all'" class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md px-3 py-2.5">Every transaction ever recorded, with no date window.</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="showExport = false" :disabled="exporting" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg disabled:opacity-50">Cancel</button>
                <button type="button" @click="download()" :disabled="! canExport() || exporting" class="flex-1 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg disabled:opacity-50"
                    x-text="exporting ? 'Preparing…' : 'Download PDF'">Download PDF</button>
            </div>
        </div>
    </div>

    {{-- ══════════ VIEW SLIDE-IN ══════════ --}}
    <div x-cloak x-show="showView" class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" @click="showView = false"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate" x-text="viewRow.reason"></h2>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="(viewRow.type === 'expense' ? 'Expense' : 'Credit') + ' · ' + (viewRow.source || '') + ' · ' + (viewRow.date || '')"></p>
                </div>
                <button @click="showView = false" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4 text-sm">
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">Amount</span>
                    <span class="col-span-2 font-semibold" :class="viewRow.type === 'expense' ? 'text-red-600' : 'text-emerald-600'">₹<span x-text="viewRow.amount"></span></span></div>
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">Date</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.date + (viewRow.time ? ' · ' + viewRow.time : '')"></span></div>
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">Source</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.source"></span></div>
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">From</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.from"></span></div>
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">To</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.to"></span></div>
                <div class="grid grid-cols-3 gap-3" x-show="viewRow.collectedBy"><span class="text-xs text-gray-400 uppercase tracking-wider">Collected by</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.collectedBy"></span></div>
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">Mode</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.mode"></span></div>
                <div class="grid grid-cols-3 gap-3" x-show="viewRow.receipt"><span class="text-xs text-gray-400 uppercase tracking-wider">Receipt / Ref.</span>
                    <span class="col-span-2 text-gray-800 font-medium break-words" x-text="viewRow.receipt"></span></div>
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">Running balance</span>
                    <span class="col-span-2 text-gray-800 font-medium">₹<span x-text="viewRow.balance"></span></span></div>
                <div class="grid grid-cols-3 gap-3"><span class="text-xs text-gray-400 uppercase tracking-wider">Entry</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.manual ? 'Manual — can be edited or deleted' : 'Automatic — corrected where it was made'"></span></div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button @click="showView = false" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
            </div>
        </div>
    </div>
</div>
