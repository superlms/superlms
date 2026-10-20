<div class="min-h-screen bg-gray-50">
    @php
        $money = fn ($v) => '₹' . number_format((float) $v, 2);
        $openStudent = $ledger['student'] ?? null;
        // Add, Submit and View pick a student; the other tabs are the ordinary fee's own screens.
        $studentTab = in_array($tab, ['add', 'submit', 'view'], true);
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
                    @if ($studentTab)
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total Fee: <strong class="text-gray-800">₹{{ number_format($school['total'], 0) }}</strong></span>
                        <span class="px-4">Collected: <strong class="text-emerald-600">₹{{ number_format($school['collected'], 0) }}</strong></span>
                        <span class="pl-4">Balance: <strong class="{{ $school['balance'] > 0 ? 'text-rose-500' : 'text-gray-800' }}">₹{{ number_format($school['balance'], 0) }}</strong></span>
                    </div>
                    @elseif ($tab === 'payments')
                        {{-- Total Fee (the students' own fees, and the bus) and Collected, for the filters picked --}}
                        <div class="hidden lg:block">@include('livewire.partials.payments-header-figures')</div>
                    @elseif ($tab === 'qr' && $qrSubTab === 'payments')
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1 divide-x divide-gray-200">
                            <span class="pr-4">
                                {{ $qrDate !== '' ? \Carbon\Carbon::parse($qrDate)->format('d M') . ':' : 'Total:' }}
                                <strong class="text-gray-800">{{ $qrStats['total'] ?? 0 }}</strong>
                                <span class="text-gray-400">· ₹{{ number_format($qrStats['amount'] ?? 0, 0) }}</span>
                            </span>
                            <span class="px-4">To check: <strong class="text-amber-600">{{ $qrStats['pending'] ?? 0 }}</strong></span>
                            <span class="px-4">Approved: <strong class="text-emerald-600">{{ $qrStats['approved'] ?? 0 }}</strong></span>
                            <span class="pl-4">Rejected: <strong class="text-rose-500">{{ $qrStats['rejected'] ?? 0 }}</strong></span>
                        </div>
                    @elseif ($tab === 'qr')
                        {{-- The QR component owns the slide-in panel; the button lives here. --}}
                        <button wire:click="$dispatchTo('admin.payment-qr', 'payment-qr-add')"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ ($qrExists ?? false) ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' : 'M12 4v16m8-8H4' }}" /></svg>
                            <span>{{ ($qrExists ?? false) ? 'Edit QR' : 'Add QR' }}</span>
                        </button>
                    @elseif ($tab === 'cycle')
                        @include('livewire.partials.fee-cycle-actions')
                    @endif

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
            @if ($studentTab)
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total Fee: <strong class="text-gray-800">₹{{ number_format($school['total'], 0) }}</strong></span>
                <span>Collected: <strong class="text-emerald-600">₹{{ number_format($school['collected'], 0) }}</strong></span>
                <span>Balance: <strong class="{{ $school['balance'] > 0 ? 'text-rose-500' : 'text-gray-800' }}">₹{{ number_format($school['balance'], 0) }}</strong></span>
            </div>
            @elseif ($tab === 'payments')
                <div class="lg:hidden mt-3 text-xs">@include('livewire.partials.payments-header-figures')</div>
            @endif
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($tabs as $key => $label)
                    <button wire:click="setTab('{{ $key }}')" wire:key="cf-tab-{{ $key }}"
                        class="relative inline-flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                               {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                        @if ($key === 'qr' && ($qrPending ?? 0) > 0)
                            <span title="{{ $qrPending }} to check" class="min-w-[18px] h-[18px] px-1 rounded-full bg-amber-500 text-white text-[10px] font-bold inline-flex items-center justify-center">{{ $qrPending > 99 ? '99+' : $qrPending }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        @if ($tab === 'payments')
            {{-- The ordinary fee's Payments filters: Fee / Transport, dates, class, section, mode, student --}}
            @include('livewire.partials.payments-header')
        @elseif ($tab === 'cycle')
            {{-- Fee Cycle | Calculator, and the Calculator's class filter --}}
            @include('livewire.partials.fee-cycle-header')
        @elseif ($tab === 'qr')
            {{-- Two tabs: what students reported, and the school's own QR / UPI ID --}}
            <div class="border-t border-gray-200 px-4 sm:px-6">
                <div class="flex gap-1">
                    @foreach (['payments' => 'QR Payments', 'qr' => 'Payment QR'] as $sub => $subLabel)
                        <button wire:key="cf-qr-sub-{{ $sub }}" wire:click="setQrSubTab('{{ $sub }}')"
                            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $qrSubTab === $sub ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">{{ $subLabel }}</button>
                    @endforeach
                </div>
            </div>
            @if ($qrSubTab === 'payments')
                <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            Filter by:
                        </div>
                        <input wire:model.live.debounce.300ms="qrSearch" type="text" placeholder="Search student, admission no. or UTR…"
                            class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <select wire:model.live="qrStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="pending">To check</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="">All Status</option>
                        </select>
                        <select wire:model.live="qrFeeType" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">All Fees</option>
                            <option value="academic">Fee</option>
                            <option value="transport">Transport</option>
                        </select>
                        <input wire:model.live="qrDate" type="date" title="Paid on"
                            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" />
                        @if ($qrSearch !== '' || $qrFeeType !== '' || $qrDate !== '' || $qrStatus !== 'pending')
                            <button wire:click="qrClearFilters"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                Clear
                            </button>
                        @endif
                    </div>
                    <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
                        <span>{{ $qrDate !== '' ? \Carbon\Carbon::parse($qrDate)->format('d M') . ':' : 'Total:' }} <strong class="text-gray-800">{{ $qrStats['total'] ?? 0 }}</strong></span>
                        <span>To check: <strong class="text-amber-600">{{ $qrStats['pending'] ?? 0 }}</strong></span>
                        <span>Approved: <strong class="text-emerald-600">{{ $qrStats['approved'] ?? 0 }}</strong></span>
                        <span>Rejected: <strong class="text-rose-500">{{ $qrStats['rejected'] ?? 0 }}</strong></span>
                    </div>
                </div>
            @endif
        @endif

        {{-- Grey filter band: Class → Section → Student --}}
        @if ($studentTab)
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
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6 space-y-5">
        @if ($tab === 'payments')
            {{-- Every payment, with its slip — the ordinary fee's Payments list --}}
            @include('livewire.partials.payments-table')

        @elseif ($tab === 'qr')
            @if ($qrSubTab === 'qr')
                @livewire('admin.payment-qr', key('cf-qr-code'))
            @else
                @livewire('admin.qr-payments', key('cf-qr-payments'))
            @endif

        @elseif ($tab === 'cycle')
            {{-- Installments as a % of each student's own fee; the Calculator splits each one's --}}
            <p class="text-xs text-gray-500">
                Each installment is its % of a student's own fee (after the Registration Fee); Submit Fee and View Fee show every student's installments.
            </p>
            @include('livewire.partials.fee-cycle-panel')

        @elseif (!$standardId)
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
                $pct = $tot['total'] > 0 ? (int) min(100, round($tot['paid'] / $tot['total'] * 100)) : 0;
            @endphp

            {{-- ══════════ Student on the left, their fee structure on the right ══════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">

                {{-- ── 1. The student, and where their fee stands ── --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-[11px] font-medium flex-shrink-0">
                            {{ mb_strtoupper(mb_substr($ledger['name'], 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-700 truncate">{{ $ledger['name'] }}</h3>
                            <p class="text-[11px] text-gray-400">
                                {{ $stu->standard->name ?? '—' }}{{ $stu->section ? ' · ' . $stu->section->name : '' }}
                                · Adm {{ $stu->admission_no ?: '—' }} · Roll {{ $stu->roll_no ?: '—' }}
                            </p>
                        </div>
                    </div>

                    {{-- Details, as flat label rows --}}
                    <div class="divide-y divide-gray-100 border-b border-gray-100">
                        @foreach ([
                            "Father's name"   => $stu->father_name,
                            "Mother's name"   => $stu->mother_name,
                            'Phone'           => $stu->phone,
                            'Admission no.'   => $stu->admission_no,
                            'Roll no.'        => $stu->roll_no,
                            'Class / section' => ($stu->standard->name ?? '—') . ($stu->section ? ' · ' . $stu->section->name : ''),
                        ] as $label => $value)
                            <div wire:key="cf-detail-{{ \Illuminate\Support\Str::slug($label) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm">
                                <span class="w-28 flex-shrink-0 text-xs text-gray-500">{{ $label }}</span>
                                <span class="flex-1 text-gray-800 truncate">{{ $value ?: '—' }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Collection --}}
                    <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_3.5rem] items-center bg-gray-50 text-xs text-gray-500">
                        <span class="px-4 py-2 text-right">Total</span>
                        <span class="px-4 py-2 text-right">Paid</span>
                        <span class="px-4 py-2 text-right">Remaining</span>
                        <span class="px-4 py-2 text-right">Done</span>
                    </div>
                    <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_3.5rem] items-center border-t border-gray-100 text-sm">
                        <span class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $money($tot['total']) }}</span>
                        <span class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $money($tot['paid']) }}</span>
                        <span class="px-4 py-2.5 text-right tabular-nums {{ $tot['balance'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $money($tot['balance']) }}</span>
                        <span class="px-4 py-2.5 text-right text-gray-500 tabular-nums">{{ $pct }}%</span>
                    </div>
                </div>

                {{-- ── 2. Fee structure — the student's own particulars ── --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Fee structure</h3>
                            <p class="text-[11px] text-gray-400">{{ $ledger['items']->count() }} {{ \Illuminate\Support\Str::plural('particular', $ledger['items']->count()) }}</p>
                        </div>
                        <span class="text-[11px] text-gray-600">Payable <strong>{{ $money($tot['total']) }}</strong></span>
                    </div>

                    <div class="divide-y divide-gray-100 text-sm">
                        @forelse ($ledger['items'] as $n => $item)
                            <div wire:key="cf-item-{{ $item->id }}" class="flex items-center gap-3 px-4 py-2.5">
                                <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $n + 1 }}</span>
                                <span class="flex-1 text-gray-800 truncate">{{ $item->fee_name }}</span>
                                <span class="text-gray-700 tabular-nums">{{ $money($item->amount) }}</span>
                                @if ($tab === 'add')
                                    <button wire:click="confirmDeleteItem({{ $item->id }})" title="Remove"
                                        class="w-7 h-7 -my-1 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="px-4 py-10 text-center text-xs text-gray-400">
                                No fee added yet.@if ($tab === 'add') Use Add Fee above to put the particulars on this student.@endif
                            </p>
                        @endforelse
                        @if ($ledger['items']->isNotEmpty())
                            <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-50/60">
                                <span class="flex-1 text-gray-800 font-medium">Payable</span>
                                <span class="text-gray-900 font-semibold tabular-nums">{{ $money($tot['total']) }}</span>
                                @if ($tab === 'add')
                                    <span class="w-7 flex-shrink-0"></span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ══════════ FEE CYCLE — the student's fee split as the school's Fee Cycle splits it ══════════ --}}
            @if ($tab !== 'add')
                @php
                    $inst = $ledger['installments'] ?? [];
                    $instTone = [
                        'Paid'    => 'bg-emerald-50 text-emerald-700',
                        'Partial' => 'bg-amber-50 text-amber-700',
                        'Overdue' => 'bg-red-50 text-red-600',
                        'Due'     => 'bg-gray-100 text-gray-600',
                        'Nil'     => 'bg-gray-50 text-gray-400',
                    ];
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Installments</h3>
                            <p class="text-[11px] text-gray-400">This student's fee on the school's Fee Cycle — what they paid goes against the earliest first</p>
                        </div>
                        @if ($inst)
                            <span class="text-[11px] text-gray-600">
                                Cleared <strong>{{ collect($inst)->where('status', 'Paid')->count() }}</strong> of {{ collect($inst)->where('amount', '>', 0)->count() }}
                            </span>
                        @endif
                    </div>
                    @if (!$inst)
                        <p class="px-4 py-8 text-center text-xs text-gray-400">
                            No fee cycle yet — add one in the Fee Cycle tab to split the fee into installments.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-left text-xs text-gray-500">
                                        <th class="px-4 py-2 font-normal">Installment</th>
                                        <th class="px-4 py-2 font-normal text-right">%</th>
                                        <th class="px-4 py-2 font-normal">Due date</th>
                                        <th class="px-4 py-2 font-normal text-right">Amount</th>
                                        <th class="px-4 py-2 font-normal text-right">Paid</th>
                                        <th class="px-4 py-2 font-normal text-right">Remaining</th>
                                        <th class="px-4 py-2 font-normal text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($inst as $n => $row)
                                        <tr wire:key="cf-inst-{{ $n }}">
                                            <td class="px-4 py-2.5">
                                                <p class="text-gray-800">{{ $row['label'] }}</p>
                                                @if ($row['span'])<p class="text-[11px] text-gray-400">{{ $row['span'] }}</p>@endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right text-gray-500 tabular-nums">{{ $row['percent'] !== null ? rtrim(rtrim(number_format($row['percent'], 2), '0'), '.') . '%' : '—' }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $row['due']?->format('d M Y') ?? '—' }}</td>
                                            <td class="px-4 py-2.5 text-right text-gray-800 tabular-nums">{{ $money($row['amount']) }}</td>
                                            <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $money($row['paid']) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums {{ $row['left'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $money($row['left']) }}</td>
                                            <td class="px-4 py-2.5 text-center">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium {{ $instTone[$row['status']] ?? $instTone['Due'] }}">{{ $row['status'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ══════════ 3. PAYMENTS — full width, each with its slip (as the regular fee lists them) ══════════ --}}
            @if ($tab !== 'add')
                @php $payCols = 'grid grid-cols-[2.5rem_9.5rem_7.5rem_7rem_9rem_minmax(0,1fr)_7rem_4.5rem] items-center'; @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Payments</h3>
                            <p class="text-[11px] text-gray-400">Every payment, newest first, each with its slip</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
                            <span>Entries <strong>{{ $ledger['payments']->count() }}</strong></span>
                            <span>Collected <strong>{{ $money($tot['paid']) }}</strong></span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="min-w-[860px] text-sm">
                            <div class="{{ $payCols }} bg-gray-50 text-xs text-gray-500">
                                <span class="px-4 py-2">#</span>
                                <span class="px-4 py-2">Receipt</span>
                                <span class="px-4 py-2">Date</span>
                                <span class="px-4 py-2">Mode</span>
                                <span class="px-4 py-2">Collected by</span>
                                <span class="px-4 py-2">Remark</span>
                                <span class="px-4 py-2 text-right">Amount</span>
                                <span class="px-4 py-2 text-center">Slip</span>
                            </div>
                            @forelse ($ledger['payments'] as $n => $p)
                                <div wire:key="cf-pay-{{ $p->id }}" class="{{ $payCols }} border-t border-gray-100">
                                    <span class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</span>
                                    <span class="px-4 py-2.5 text-xs text-gray-700 font-mono truncate">{{ $p->receipt_number }}</span>
                                    <span class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ optional($p->payment_date)->format('d M Y') ?? '—' }}</span>
                                    <span class="px-4 py-2.5 text-gray-600">{{ $modes[$p->payment_mode] ?? ucfirst(str_replace('_', ' ', $p->payment_mode)) }}</span>
                                    <span class="px-4 py-2.5 text-gray-600 truncate">{{ $p->submitted_by ?: '—' }}</span>
                                    <span class="px-4 py-2.5 text-gray-500 truncate" title="{{ $p->remark }}">{{ $p->remark ?: '—' }}</span>
                                    <span class="px-4 py-2.5 text-right text-gray-800 tabular-nums">{{ $money($p->amount) }}</span>
                                    <span class="px-2 py-2.5 text-center whitespace-nowrap">
                                        <a href="{{ $this->receiptUrl($p->id) }}" target="_blank" rel="noopener" title="Open slip"
                                            class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                        </a>
                                        {{-- A payment is never deleted; Submit Fee may correct its date. --}}
                                        @if ($tab === 'submit')
                                            <button type="button" wire:click="openPaymentDateEdit({{ $p->id }})" title="Edit date"
                                                class="inline-flex p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <p class="px-4 py-10 text-center text-sm text-gray-400 border-t border-gray-100">
                                    No payments yet — collected fees appear here with their slips.
                                </p>
                            @endforelse
                            @if ($ledger['payments']->isNotEmpty())
                                <div class="flex items-center gap-3 px-4 py-2.5 border-t border-gray-100 bg-gray-50/60">
                                    <span class="flex-1 text-gray-700">Total</span>
                                    <span class="w-28 text-right text-gray-800 font-medium tabular-nums">{{ $money($tot['paid']) }}</span>
                                    <span class="w-[4.5rem] flex-shrink-0"></span>
                                </div>
                            @endif
                        </div>
                    </div>
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                            <select wire:model.live="paySectionId" @disabled(!$payStandardId)
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

    {{-- ══════════════════════════════════════════════════
         EDIT PAYMENT DATE — the pencil beside a payment in Submit Fee
         (as the regular fee's); only the date changes
    ══════════════════════════════════════════════════ --}}
    @if ($showPaymentDateEdit)
        <div class="lms-cover fixed inset-x-0 bottom-0 z-[60] flex items-center justify-center p-4" style="top: var(--lms-nav-h, 65px);">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="closePaymentDateEdit"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <h3 class="text-base font-semibold text-gray-900">Edit Payment Date</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $editPaymentInfo['receipt_number'] ?? '' }} · {{ $money($editPaymentInfo['amount'] ?? 0) }}
                </p>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="editPaymentDate" max="{{ today()->toDateString() }}" wire:keydown.enter="savePaymentDate"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('editPaymentDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="closePaymentDateEdit" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="savePaymentDate" wire:loading.attr="disabled" wire:target="savePaymentDate"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="savePaymentDate">Save</span>
                        <span wire:loading wire:target="savePaymentDate">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
