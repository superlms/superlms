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

        openExport() { this.showExport = true },
        canExport() {
            if (this.expMode === 'all') return true;
            if (this.expMode === 'day') return !! this.expDay;
            return !! (this.expFrom && this.expTo);
        },
        {{-- The PDF comes down as a file, as the Students export does; the popup
             waits on it and then closes. --}}
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
        exportUrl() {
            const params = new URLSearchParams();
            if (this.expMode === 'all') {
                params.set('overall', '1');
            } else if (this.expMode === 'day') {
                params.set('start_date', this.expDay);
                params.set('end_date', this.expDay);
            } else {
                params.set('start_date', this.expFrom);
                params.set('end_date', this.expTo);
            }
            {{-- The component supplies this so the accounts panel can render this same view. --}}
            return @js($statementUrl) + '?' + params.toString();
        },
    }">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + actions)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        {{-- Title row: same shape and height as the Transportation header —
             title on the left, divided analytics then the buttons on the right. --}}
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Ledger</h1>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200 mr-1">
                        {{-- All four follow the period picked below. --}}
                        <span class="pr-4">Net: <strong class="{{ $periodNet >= 0 ? 'text-emerald-600' : 'text-red-600' }}">₹{{ number_format($periodNet, 2) }}</strong></span>
                        <span class="px-4">Credit: <strong class="text-emerald-600">₹{{ number_format($periodCredit, 2) }}</strong></span>
                        <span class="px-4">Expense: <strong class="text-red-600">₹{{ number_format($periodExpense, 2) }}</strong></span>
                        <span class="pl-4">Closing: <strong class="text-blue-600">₹{{ number_format($closingBalance, 2) }}</strong></span>
                    </div>

                    {{-- Export and Add, as the Students header has them. Export opens the
                         period dialog; Add asks "credit or expense?" first. --}}
                    <button type="button" @click="openExport()"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200
                               text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                    <button wire:click="openAdd"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add</span>
                    </button>
                </div>
            </div>

            {{-- Mobile/Tablet stats — inside the same block, so the desktop
                 header stays exactly as tall as Transportation's. --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Net: <strong class="{{ $periodNet >= 0 ? 'text-emerald-600' : 'text-red-600' }}">₹{{ number_format($periodNet, 2) }}</strong></span>
                <span>Credit: <strong class="text-emerald-600">₹{{ number_format($periodCredit, 2) }}</strong></span>
                <span>Expense: <strong class="text-red-600">₹{{ number_format($periodExpense, 2) }}</strong></span>
                <span>Closing: <strong class="text-blue-600">₹{{ number_format($closingBalance, 2) }}</strong></span>
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

                <button wire:click="thisMonth"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border
                        {{ $isThisMonth ? 'text-white bg-blue-600 border-blue-600 hover:bg-blue-700' : 'text-gray-700 bg-white border-gray-200 hover:bg-gray-100' }}">
                    This Month
                </button>
                <button wire:click="overall"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border
                        {{ $isOverall ? 'text-white bg-blue-600 border-blue-600 hover:bg-blue-700' : 'text-gray-700 bg-white border-gray-200 hover:bg-gray-100' }}">
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

                {{-- Clear: shown once the window is anything but the one the page
                     opens on, right after the End date (not pushed to the far side). --}}
                @unless ($isThisMonth)
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endunless
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
            {{-- Fixed percentage columns: the table is always exactly as wide as
                 the card, so the listing never scrolls sideways — long values
                 wrap onto the next line instead. --}}
            <div>
                <table class="w-full table-fixed">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="w-[10%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            {{-- A fee row's particulars are two short lines now and its From
                                 two as well, so From takes some of the width. --}}
                            <th class="w-[20%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Particulars</th>
                            <th class="w-[18%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">From</th>
                            <th class="w-[12%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">To</th>
                            <th class="w-[9%] px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mode</th>
                            <th class="w-[12%] px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="w-[11%] px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="w-[8%] px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($entries as $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-3 text-sm text-gray-600">
                                    {{ $row['date']->format('d M Y') }}
                                    @if (!empty($row['time']))
                                        <span class="block text-[11px] text-gray-400">{{ $row['time'] }}</span>
                                    @endif
                                </td>
                                @if (!empty($row['kind']))
                                {{-- A fee: only its kind — Academic, Transport or Penalty — and
                                     under it the student's admission number. The receipt and the
                                     rest are on View. --}}
                                <td class="px-3 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $row['kind'] }}</p>
                                    <p class="text-xs text-gray-400 break-words">{{ $row['admission_no'] ?? '—' }}</p>
                                </td>
                                @elseif (in_array($row['source'], ['Admission Fee', 'Salary'], true))
                                {{-- An admission fee or a salary: only whose it is — the student,
                                     the employee — and under it the type, as plain text. The
                                     month and the rest are on View. --}}
                                <td class="px-3 py-3">
                                    <p class="text-sm font-medium text-gray-900 break-words">{{ $row['party'] ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $row['source'] === 'Salary' ? 'Salary' : 'Admission' }}</p>
                                </td>
                                @else
                                <td class="px-3 py-3">
                                    @php
                                        // Every automatic source gets its own colour so the credits
                                        // (academic / transport / admission fee) and the expense
                                        // (salary) are told apart at a glance. Manual rows carry no
                                        // badge here — they are tagged in the Mode column instead.
                                        $srcClass = match ($row['source']) {
                                            'Salary'        => 'bg-orange-50 text-orange-600',
                                            'Transport Fee' => 'bg-cyan-50 text-cyan-700',
                                            'Admission Fee' => 'bg-teal-50 text-teal-700',
                                            default         => 'bg-blue-50 text-blue-600',
                                        };
                                    @endphp
                                    {{-- Particulars reads in full again, wrapping
                                         inside its column rather than being cut. --}}
                                    <div class="flex items-start gap-2 flex-wrap">
                                        <p class="text-sm font-medium text-gray-900 break-words">{{ $row['reason'] }}</p>
                                        @if ($row['source'] !== 'Manual')
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded flex-shrink-0 {{ $srcClass }}">
                                                {{ $row['source'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                @endif
                                {{-- The school's own side reads as whose entry it is — Admin,
                                     Sub-admin or Accounts — not as the school's name. A student
                                     reads as their name, the father's name small under it. --}}
                                <td class="px-3 py-3 text-sm text-gray-600 break-words">
                                    @if (!empty($row['student_name']))
                                        {{ $row['student_name'] }}
                                        @if (!empty($row['father_name']))
                                            <span class="block text-xs text-gray-400">{{ $row['father_name'] }}</span>
                                        @endif
                                    @else
                                        {{ $row['from_label'] }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-600 break-words">{{ $row['to_label'] }}</td>
                                <td class="px-3 py-3 text-sm text-gray-500">
                                    {{-- Payment mode, with how the row got here underneath it. --}}
                                    <div class="break-words">{{ $row['mode'] ?: '—' }}</div>
                                    {{-- Plain text, small and grey — no coloured tag. --}}
                                    @if (empty($row['manual_id']))
                                        <span class="block text-[11px] text-gray-400" title="Recorded automatically — view only">Auto</span>
                                    @else
                                        <span class="block text-[11px] text-gray-400" title="Added by hand in the ledger">Manual</span>
                                    @endif
                                </td>
                                {{-- One amount column: the figure, and under it — small — whether
                                     it is a credit or an expense. --}}
                                <td class="px-3 py-3 text-right">
                                    <span class="block text-sm font-semibold {{ $row['type'] === 'expense' ? 'text-red-600' : 'text-emerald-600' }}">₹{{ number_format($row['amount'], 2) }}</span>
                                    <span class="block text-[11px] text-gray-400">{{ $row['type'] === 'expense' ? 'Expense' : 'Credit' }}</span>
                                </td>
                                <td class="px-3 py-3 text-right text-sm font-semibold {{ $row['balance'] >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                                    ₹{{ number_format($row['balance'], 2) }}
                                </td>
                                <td class="px-3 py-3">                                    <div class="flex items-center justify-center gap-1">
                                        {{-- View is available for every row (auto + manual) --}}
                                        <button title="View details"
                                            @click="viewRow = {
                                                date: @js($row['date']->format('d M Y')),
                                                time: @js($row['time'] ?? null),
                                                reason: @js($row['reason']),
                                                source: @js($row['source']),
                                                from: @js($row['from_label']),
                                                to: @js($row['to_label']),
                                                collectedBy: @js($row['collected_by'] ?? null),
                                                mode: @js($row['mode'] ?: '—'),
                                                type: @js($row['type']),
                                                amount: @js(number_format($row['amount'], 2)),
                                                balance: @js(number_format($row['balance'], 2)),
                                                manual: @js(!empty($row['manual_id'])),
                                                editable: @js(!empty($row['editable'])),
                                            }; showView = true"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        {{-- Edit only for manual entries, and only for a
                                             week after they were added. Nothing is ever
                                             deleted from the ledger. --}}
                                        @if (!empty($row['manual_id']) && ($row['editable'] ?? false))
                                            <button wire:click="openEdit({{ $row['manual_id'] }})" title="Edit entry"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-16 text-center">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit' : 'Add' }} {{ $modalType === 'expense' ? 'Expense' : ($modalType === 'credit' ? 'Credit' : 'Entry') }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $modalType === 'expense' ? 'Record money going out' : ($modalType === 'credit' ? 'Record money coming in' : 'Choose what you are adding') }}
                        </p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    {{-- Adding: first say which it is; the rest of the form follows, every
                         field of it compulsory. An entry being edited keeps its type and
                         everything else but its date: the other fields are shown, locked. --}}
                    @if ($editingId)
                        <div class="rounded-lg px-3.5 py-2.5 text-sm font-medium
                            {{ $modalType === 'expense' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                            This entry is recorded under <strong>{{ $modalType === 'expense' ? 'Expenses' : 'Credits' }}</strong>.
                            Only its date can be changed.
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                What are you adding? <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                {{-- The two kinds, by name alone. --}}
                                @foreach (['credit' => 'Credit', 'expense' => 'Expense'] as $value => $label)
                                    <button type="button" wire:click="chooseType('{{ $value }}')"
                                        class="px-3.5 py-3 text-left border-2 rounded-md transition-all
                                            {{ $modalType === $value ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:bg-gray-50' }}">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($modalType === 'credit' || $modalType === 'expense')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mDate" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('mDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mAmount" type="number" step="0.01" min="0" placeholder="0.00" @disabled($editingId)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed">
                            @error('mAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">From <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mParty" type="text" @disabled($editingId)
                                placeholder="{{ $modalType === 'expense' ? 'Paid from (source / account)' : 'Received from (payer)' }}"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed">
                            @error('mParty')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mode <span class="text-red-500">*</span></label>
                            <select wire:model.defer="mMode" @disabled($editingId)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed">
                                @foreach ($modes as $mode)
                                    <option value="{{ $mode }}">{{ $mode }}</option>
                                @endforeach
                            </select>
                            @error('mMode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @if ($modalType === 'credit')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected by <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mCollectedBy" type="text" placeholder="Staff member who collected the money" @disabled($editingId)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed">
                            @error('mCollectedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">To <span class="text-red-500">*</span></label>
                            <input wire:model.defer="mPartyTo" type="text" placeholder="Paid to (payee / vendor)" @disabled($editingId)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed">
                            @error('mPartyTo')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="mReason" rows="3" placeholder="What is this for?" @disabled($editingId)
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed"></textarea>
                        @error('mReason')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    {{-- Nothing to save until "credit or expense?" is answered. --}}
                    @if ($modalType === 'credit' || $modalType === 'expense')
                        <button wire:click="saveManual" wire:loading.attr="disabled" wire:target="saveManual"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveManual">{{ $editingId ? 'Update Date' : 'Save ' . ($modalType === 'expense' ? 'Expense' : 'Credit') }}</span>
                            <span wire:loading wire:target="saveManual">Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- EXPORT STATEMENT POPUP — pick the period before the PDF is built. The
         logout popup's card: in the middle of the window, everything behind it
         (top bar and sidebar too, hence `lms-cover` while it is open) dimmed and
         blurred. --}}
    <div x-cloak x-show="showExport" :class="showExport && 'lms-cover'"
        @click.self="exporting || (showExport = false)"
        @keydown.escape.window="exporting || (showExport = false)"
        class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
        <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Export Statement</h3>
                    <p class="text-xs text-gray-400">Choose the period the PDF should cover.</p>
                </div>
            </div>

            <div class="space-y-4 mb-5">
                {{-- All time first, and the one the popup opens on; then a single
                     day, then a date range. --}}
                <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg">
                    <button type="button" @click="expMode = 'all'"
                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md"
                        :class="expMode === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        All time
                    </button>
                    <button type="button" @click="expMode = 'day'"
                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md"
                        :class="expMode === 'day' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        Single day
                    </button>
                    <button type="button" @click="expMode = 'range'"
                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md"
                        :class="expMode === 'range' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        Date range
                    </button>
                </div>

                <div x-show="expMode === 'range'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">From</label>
                        <input type="date" x-model="expFrom" :max="expTo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">To</label>
                        <input type="date" x-model="expTo" :min="expFrom"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div x-show="expMode === 'day'">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Date</label>
                    <input type="date" x-model="expDay"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <p x-show="expMode === 'all'" class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md px-3 py-2.5">
                    Every transaction ever recorded, with no date window.
                </p>
            </div>

            {{-- Cancel on the left, Download on the right. --}}
            <div class="flex items-center gap-2">
                <button type="button" @click="showExport = false" :disabled="exporting"
                    class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm
                           font-medium rounded-lg transition-colors disabled:opacity-50">
                    Cancel
                </button>
                <button type="button" @click="download()" :disabled="! canExport() || exporting"
                    class="flex-1 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm
                           font-medium rounded-lg transition-colors disabled:opacity-50"
                    x-text="exporting ? 'Preparing…' : 'Download PDF'">
                    Download PDF
                </button>
            </div>
        </div>
    </div>

    {{-- VIEW TRANSACTION SLIDE-IN PANEL (client-side, works for every row).
         Same plain label/value layout as the Exams view panel. --}}
    <div x-cloak x-show="showView" class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" @click="showView = false"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate" x-text="viewRow.reason"></h2>
                    <p class="text-xs text-gray-500 mt-0.5"
                        x-text="(viewRow.type === 'expense' ? 'Expense' : 'Credit') + ' · ' + (viewRow.source || '') + ' · ' + (viewRow.date || '')"></p>
                </div>
                <button @click="showView = false" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Type</span>
                    <span class="col-span-2 font-medium"
                        :class="viewRow.type === 'expense' ? 'text-red-600' : 'text-emerald-600'"
                        x-text="viewRow.type === 'expense' ? 'Expense' : 'Credit'"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Amount</span>
                    <span class="col-span-2 font-semibold"
                        :class="viewRow.type === 'expense' ? 'text-red-600' : 'text-emerald-600'">₹<span x-text="viewRow.amount"></span></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Date</span>
                    <span class="col-span-2 text-gray-800 font-medium"
                        x-text="viewRow.date + (viewRow.time ? ' · ' + viewRow.time : '')"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Source</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.source"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">From</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.from"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">To</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.to"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm" x-show="viewRow.collectedBy">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Collected by</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.collectedBy"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Mode</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.mode"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Particulars</span>
                    <span class="col-span-2 text-gray-800 font-medium" x-text="viewRow.reason"></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Running balance</span>
                    <span class="col-span-2 text-gray-800 font-medium">₹<span x-text="viewRow.balance"></span></span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Entry</span>
                    {{-- Fee collections and salary payouts land here on their own;
                         they can only be corrected in the module they came from.
                         A manual entry is editable for a week, then it closes. --}}
                    <span class="col-span-2 text-gray-800 font-medium"
                        x-text="viewRow.manual
                            ? (viewRow.editable ? 'Manual — editable for 7 days from entry' : 'Manual — edit window closed')
                            : 'Automatic — view only'"></span>
                </div>
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button @click="showView = false" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
            </div>
        </div>
    </div>
</div>
