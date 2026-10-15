<div class="min-h-screen bg-gray-50">
    @php
        $money = fn ($v) => '₹' . number_format((float) $v, 2);
        $cfTabs = [
            'add'    => ['Add Fee', 'M12 4v16m8-8H4'],
            'submit' => ['Submit Fee', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            'view'   => ['View Fee', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
        ];
        $openStudent = $ledger['student'] ?? null;
    @endphp

    {{-- ══════════════════════════════════════════════════
         HEADER — title, the school's figures, the tab's button,
         the three tabs and Class → Section → Student
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Fees</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total Fee: <strong class="text-gray-800">₹{{ number_format($school['total'], 0) }}</strong></span>
                        <span class="px-4">Collected: <strong class="text-emerald-600">₹{{ number_format($school['collected'], 0) }}</strong></span>
                        <span class="pl-4">Balance: <strong class="{{ $school['balance'] > 0 ? 'text-rose-500' : 'text-gray-800' }}">₹{{ number_format($school['balance'], 0) }}</strong></span>
                    </div>

                    @if ($tab === 'add')
                        <button wire:click="openFeePanel" @disabled(!$studentId)
                            title="{{ $studentId ? 'Add or change this student\'s fee' : 'Pick a student first' }}"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Add Fee</span>
                            <span class="sm:hidden">Add</span>
                        </button>
                    @elseif ($tab === 'submit')
                        <button wire:click="openPayPanel"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Update Fee</span>
                            <span class="sm:hidden">Update</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Mobile/Tablet figures --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total Fee: <strong class="text-gray-800">₹{{ number_format($school['total'], 0) }}</strong></span>
                <span>Collected: <strong class="text-emerald-600">₹{{ number_format($school['collected'], 0) }}</strong></span>
                <span>Balance: <strong class="{{ $school['balance'] > 0 ? 'text-rose-500' : 'text-gray-800' }}">₹{{ number_format($school['balance'], 0) }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1 overflow-x-auto">
                @foreach ($cfTabs as $key => [$label, $icon])
                    <button wire:click="setTab('{{ $key }}')" wire:key="cf-tab-{{ $key }}"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                               {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                            {{ $label }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Grey filter band: Class → Section → Student --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>

                <select wire:model.live="standardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select Class</option>
                    @foreach ($standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>

                <select data-sole-section wire:model.live="sectionId" @disabled(!$standardId)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">All Sections</option>
                    @foreach ($sections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="studentId" @disabled(!$standardId)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[200px]">
                    <option value="">
                        @if (!$standardId)
                            Pick a class first
                        @elseif ($tab !== 'add' && $students->isEmpty())
                            No student with a fee yet
                        @else
                            Select Student
                        @endif
                    </option>
                    @foreach ($students as $stu)
                        <option value="{{ $stu->id }}">{{ $stu->full_name ?: ($stu->user->name ?? 'Unknown') }}@if ($stu->admission_no) · {{ $stu->admission_no }}@endif</option>
                    @endforeach
                </select>

                @if ($standardId || $sectionId || $studentId)
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6 space-y-5">
        @if (!$standardId)
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-4 py-16 text-center">
                <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <p class="text-sm font-semibold text-gray-800">Select a class, then a student</p>
                <p class="text-xs text-gray-400 mt-1">
                    @if ($tab === 'add')
                        Then use Add Fee above to put the fee particulars on that student.
                    @elseif ($tab === 'submit')
                        Then use Update Fee above to take a payment against their fee.
                    @else
                        Only the students who have a fee set are listed.
                    @endif
                </p>
            </div>

        @elseif (!$ledger)
            {{-- ── The class's students ── --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-700">
                        {{ $tab === 'add' ? 'Students' : 'Students with a fee' }}
                    </h3>
                    <span class="text-xs text-gray-400 tabular-nums">{{ $students->count() }}</span>
                </div>
                @if ($students->isEmpty())
                    <div class="text-center py-12 px-4">
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $tab === 'add' ? 'No students in this class' : 'No student here has a fee yet' }}
                        </p>
                        @if ($tab !== 'add')
                            <p class="text-xs text-gray-400 mt-1">Put one on a student in Add Fee first.</p>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                                    <th class="px-4 py-2.5">Student</th>
                                    <th class="px-4 py-2.5 text-right">Fee</th>
                                    <th class="px-4 py-2.5 text-right">Paid</th>
                                    <th class="px-4 py-2.5 text-right">Balance</th>
                                    <th class="px-4 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($students as $stu)
                                    @php $t = $classTotals[$stu->id] ?? ['total' => 0, 'paid' => 0, 'balance' => 0]; @endphp
                                    <tr wire:key="cf-stu-{{ $stu->id }}" wire:click="pickStudent({{ $stu->id }})" class="hover:bg-gray-50 cursor-pointer">
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-gray-800">{{ $stu->full_name ?: ($stu->user->name ?? 'Unknown') }}</p>
                                            <p class="text-[11px] text-gray-400">
                                                {{ $stu->father_name ?: '—' }}@if ($stu->admission_no) · Adm {{ $stu->admission_no }}@endif
                                            </p>
                                        </td>
                                        @if ($t['total'] > 0 || $tab !== 'add')
                                            <td class="px-4 py-2.5 text-right tabular-nums text-gray-700">{{ $money($t['total']) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums text-gray-700">{{ $money($t['paid']) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums {{ $t['balance'] > 0 ? 'text-red-600 font-medium' : 'text-gray-700' }}">{{ $money($t['balance']) }}</td>
                                        @else
                                            <td class="px-4 py-2.5 text-right text-xs text-gray-400" colspan="3">No fee yet</td>
                                        @endif
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="text-xs font-medium text-blue-600">Open</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        @else
            @php
                $stu = $ledger['student'];
                $tot = $ledger['totals'];
            @endphp

            {{-- ── The student and where their fee stands ── --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-[11px] font-medium flex-shrink-0">
                            {{ mb_strtoupper(mb_substr($ledger['name'], 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-700 truncate">{{ $ledger['name'] }}</h3>
                            <p class="text-[11px] text-gray-400">
                                {{ $stu->standard->name ?? '—' }}{{ $stu->section ? ' · ' . $stu->section->name : '' }}
                                · Father {{ $stu->father_name ?: '—' }}
                                @if ($stu->admission_no) · Adm {{ $stu->admission_no }}@endif
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600">
                        <span>Total <strong class="tabular-nums">{{ $money($tot['total']) }}</strong></span>
                        <span>Paid <strong class="tabular-nums text-emerald-600">{{ $money($tot['paid']) }}</strong></span>
                        <span>Balance <strong class="tabular-nums {{ $tot['balance'] > 0 ? 'text-red-600' : '' }}">{{ $money($tot['balance']) }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- ── Fee Structure ── --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-700">Fee Structure</h3>
                    <span class="text-xs text-gray-400 tabular-nums">{{ $ledger['items']->count() }} {{ \Illuminate\Support\Str::plural('particular', $ledger['items']->count()) }}</span>
                </div>
                @if ($ledger['items']->isEmpty())
                    <div class="text-center py-12 px-4">
                        <p class="text-sm font-semibold text-gray-800">No fee added yet</p>
                        <p class="text-xs text-gray-400 mt-1">Use Add Fee above to put the particulars on this student.</p>
                    </div>
                @else
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                                <th class="px-4 py-2.5 w-12">#</th>
                                <th class="px-4 py-2.5">Particular</th>
                                <th class="px-4 py-2.5 text-right">Amount</th>
                                @if ($tab === 'add')
                                    <th class="px-4 py-2.5 w-12"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($ledger['items'] as $i => $item)
                                <tr wire:key="cf-item-{{ $item->id }}">
                                    <td class="px-4 py-2.5 text-gray-400 tabular-nums">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2.5 text-gray-800">{{ $item->fee_name }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums text-gray-800">{{ $money($item->amount) }}</td>
                                    @if ($tab === 'add')
                                        <td class="px-4 py-2.5 text-right">
                                            <button wire:click="confirmDeleteItem({{ $item->id }})" title="Remove"
                                                class="w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2.5"></td>
                                <td class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Total</td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900">{{ $money($tot['total']) }}</td>
                                @if ($tab === 'add')
                                    <td></td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- ── Payments, each with its receipt ── --}}
            @if ($tab !== 'add')
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-700">Payments</h3>
                        <span class="text-xs text-gray-400 tabular-nums">{{ $ledger['payments']->count() }}</span>
                    </div>
                    @if ($ledger['payments']->isEmpty())
                        <div class="text-center py-12 px-4">
                            <p class="text-sm font-semibold text-gray-800">No payment yet</p>
                            @if ($tab === 'submit')
                                <p class="text-xs text-gray-400 mt-1">Use Update Fee above to take one.</p>
                            @endif
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                                        <th class="px-4 py-2.5">Date</th>
                                        <th class="px-4 py-2.5">Receipt No.</th>
                                        <th class="px-4 py-2.5">Mode</th>
                                        <th class="px-4 py-2.5">Collected By</th>
                                        <th class="px-4 py-2.5">Remark</th>
                                        <th class="px-4 py-2.5 text-right">Amount</th>
                                        <th class="px-4 py-2.5 text-right">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($ledger['payments'] as $p)
                                        <tr wire:key="cf-pay-{{ $p->id }}">
                                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-700">{{ optional($p->payment_date)->format('d M Y') }}</td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-500 tabular-nums">{{ $p->receipt_number }}</td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-700">{{ $modes[$p->payment_mode] ?? ucfirst(str_replace('_', ' ', $p->payment_mode)) }}</td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-700">{{ $p->submitted_by ?: '—' }}</td>
                                            <td class="px-4 py-2.5 text-gray-500 max-w-[220px] truncate" title="{{ $p->remark }}">{{ $p->remark ?: '—' }}</td>
                                            <td class="px-4 py-2.5 text-right whitespace-nowrap tabular-nums font-medium text-gray-900">{{ $money($p->amount) }}</td>
                                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center gap-1">
                                                    <a href="{{ $this->receiptUrl($p->id) }}" target="_blank" rel="noopener"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-md">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                        Slip
                                                    </a>
                                                    @if ($tab === 'submit')
                                                        <button wire:click="confirmDeletePayment({{ $p->id }})" title="Delete payment"
                                                            class="w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50">
                                        <td class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500" colspan="5">Paid so far</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-emerald-600">{{ $money($tot['paid']) }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD FEE — particulars on the student, any number at once
    ══════════════════════════════════════════════════ --}}
    @if ($showFeePanel && $feeStudent)
        @php $rowsTotal = collect($feeRows)->sum(fn ($r) => is_numeric($r['amount'] ?? null) ? (float) $r['amount'] : 0); @endphp
        <div class="fixed inset-x-0 bottom-0 z-50 overflow-hidden" style="top: var(--lms-nav-h, 65px);">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeFeePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Add Fee</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ $feeStudent->full_name ?: ($feeStudent->user->name ?? 'Student') }}
                            · {{ $feeStudent->standard->name ?? '' }}{{ $feeStudent->section ? ' ' . $feeStudent->section->name : '' }}
                        </p>
                    </div>
                    <button wire:click="closeFeePanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <p class="text-xs text-gray-500">
                        Put each fee particular on this student — a name and an amount. Add as many as you need, then save.
                        A parent's other children's fees can be put here too.
                    </p>

                    <div class="space-y-3">
                        <div class="hidden sm:grid grid-cols-[1fr_9rem_2rem] gap-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            <span>Fee Name</span>
                            <span>Amount (₹)</span>
                            <span></span>
                        </div>
                        @foreach ($feeRows as $i => $row)
                            <div wire:key="cf-row-{{ $i }}-{{ $row['id'] ?? 'new' }}" class="grid grid-cols-[1fr_7rem_2rem] sm:grid-cols-[1fr_9rem_2rem] gap-2 items-start">
                                <div>
                                    <input type="text" wire:model="feeRows.{{ $i }}.name" placeholder="e.g. Tuition Fee"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    @error("feeRows.$i.name")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    {{-- Deferred, not .live: a debounced update raced Add particular and lost the amount. --}}
                                    <input type="number" step="0.01" min="0" wire:model="feeRows.{{ $i }}.amount" placeholder="0.00"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-right tabular-nums focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    @error("feeRows.$i.amount")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <button type="button" wire:click="removeFeeRow({{ $i }})" title="Remove"
                                    class="mt-1.5 w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @endforeach
                        @error('feeRows')<p class="text-xs text-red-500">{{ $message }}</p>@enderror

                        <button type="button" wire:click="addFeeRow"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-blue-600 border border-dashed border-blue-300 rounded-md hover:bg-blue-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            Add particular
                        </button>
                    </div>

                    <div class="flex items-center justify-between rounded-md bg-gray-50 border border-gray-200 px-4 py-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total fee</span>
                        {{-- Added up in the browser as amounts are typed ($wire is reactive). --}}
                        <span class="text-sm font-semibold tabular-nums text-gray-900"
                            x-data
                            x-text="'₹' + ($wire.feeRows || []).reduce((t, r) => t + (parseFloat(r.amount) || 0), 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ $money($rowsTotal) }}</span>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeFeePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveFee" wire:loading.attr="disabled" wire:target="saveFee"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveFee">Save Fee</span>
                        <span wire:loading wire:target="saveFee">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         UPDATE FEE — take a payment
    ══════════════════════════════════════════════════ --}}
    @if ($showPayPanel)
        <div class="fixed inset-x-0 bottom-0 z-50 overflow-hidden" style="top: var(--lms-nav-h, 65px);">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePayPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Update Fee</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Take a payment against a student's fee</p>
                    </div>
                    <button wire:click="closePayPanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="payStandardId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Class</option>
                                @foreach ($standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div data-sole-section-wrap>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                            <select data-sole-section wire:model.live="paySectionId" @disabled(!$payStandardId)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50">
                                <option value="">All Sections</option>
                                @foreach ($paySections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Student <span class="text-red-500">*</span></label>
                        <select wire:model.live="payStudentId" @disabled(!$payStandardId)
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50">
                            <option value="">
                                @if (!$payStandardId)
                                    Pick a class first
                                @elseif ($payStudents->isEmpty())
                                    No student with a fee in this class
                                @else
                                    Select Student
                                @endif
                            </option>
                            @foreach ($payStudents as $stu)
                                <option value="{{ $stu->id }}">{{ $stu->full_name ?: ($stu->user->name ?? 'Unknown') }}@if ($stu->admission_no) · {{ $stu->admission_no }}@endif</option>
                            @endforeach
                        </select>
                        @error('payStudentId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        @if ($payDue)
                            <p class="mt-1.5 text-[11px] text-gray-500">
                                Fee {{ $money($payDue['total']) }} · Paid {{ $money($payDue['paid']) }} ·
                                <span class="{{ $payDue['balance'] > 0 ? 'text-red-600 font-medium' : '' }}">Due {{ $money($payDue['balance']) }}</span>
                            </p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="1" @if ($payDue) max="{{ $payDue['balance'] }}" @endif wire:model="payAmount" placeholder="Enter amount"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('payAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="payDate" max="{{ today()->toDateString() }}"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('payDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Mode</label>
                            <select wire:model="payMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @foreach ($modes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payMode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected By <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="payCollectedBy" placeholder="Staff name"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('payCollectedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                        <input type="text" wire:model="payRemark" placeholder="Optional"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('payRemark')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePayPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="savePayment" wire:loading.attr="disabled" wire:target="savePayment"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="savePayment">Submit Payment</span>
                        <span wire:loading wire:target="savePayment">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
