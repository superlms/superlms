<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>
    @php
        $inr       = fn ($v) => '₹' . number_format((float) $v, 2);
        $inrShort  = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
        $filterIcon = 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z';
        $perSchoolTab = in_array($tab, ['whatsapp', 'sms', 'email', 'sent'], true);
        $schoolName = $school !== '' ? ($schools->firstWhere('id', (int) $school)?->name ?? null) : null;
    @endphp

    {{-- ══════════ STICKY: HEADER + TABS + FILTERS ══════════ --}}
    <div class="sticky top-0 z-40">
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 sm:min-h-9">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Messaging</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">In wallets: <strong class="{{ $totals['balance'] < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $inr($totals['balance']) }}</strong></span>
                        <span class="px-4">Recharged this month: <strong class="text-emerald-600">{{ $inr($totals['topped']) }}</strong></span>
                        <span class="px-4">Used this month: <strong class="text-blue-600">{{ $inr($totals['used']) }}</strong></span>
                        <span class="pl-4">To approve: <strong class="{{ $totals['pending'] ? 'text-amber-600' : 'text-gray-800' }}">{{ $totals['pending'] }}</strong></span>
                    </div>
                    @if ($tracked)
                        <button wire:click="openFunds({{ $school !== '' ? (int) $school : 'null' }})"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add Funds
                        </button>
                    @endif
                </div>
            </div>
            <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
                <span>In wallets: <strong class="text-gray-800">{{ $inr($totals['balance']) }}</strong></span>
                <span>Recharged: <strong class="text-emerald-600">{{ $inr($totals['topped']) }}</strong></span>
                <span>Used: <strong class="text-blue-600">{{ $inr($totals['used']) }}</strong></span>
                <span>To approve: <strong class="text-amber-600">{{ $totals['pending'] }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
            <nav class="flex gap-1 overflow-x-auto">
                @foreach (\App\Livewire\SuperAdmin\Messaging::TABS as $key => $label)
                    @continue($key === 'settings' && !$canSettings)
                    <button wire:click="setTab('{{ $key }}')"
                        class="py-3 px-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors inline-flex items-center gap-1.5
                            {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                        @if ($key === 'requests' && $totals['pending'])
                            <span class="min-w-[1.25rem] h-5 px-1.5 inline-flex items-center justify-center rounded-full bg-amber-500 text-white text-[11px] font-semibold">{{ $totals['pending'] }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Filters --}}
        @if ($tab === 'wallets')
            <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                        Filter by:
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search school…"
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 max-w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    <span class="text-gray-300">·</span>
                    <span class="text-xs font-semibold text-gray-700">Sort:</span>
                    <select wire:model.live="walletSort" class="text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700">
                        <option value="name">School (A–Z)</option>
                        <option value="balance_asc">Balance (low first)</option>
                        <option value="balance_desc">Balance (high first)</option>
                        <option value="used">Used this month</option>
                    </select>
                </div>
            </div>
        @elseif ($perSchoolTab)
            <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                        School:
                    </div>
                    <select wire:model.live="school" aria-label="School"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700 min-w-[14rem] max-w-full">
                        <option value="">{{ $tab === 'sent' ? 'Every school' : 'Pick a school…' }}</option>
                        @foreach ($schools as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @if ($schoolName && $tracked)
                        @php $w = \App\Services\MessagingWallet::balance((int) $school); @endphp
                        <span class="text-xs text-gray-500">Wallet: <strong class="{{ $w < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $inr($w) }}</strong></span>
                        <button wire:click="openStatement({{ (int) $school }})" class="text-xs font-medium text-blue-600 hover:text-blue-800">Statement →</button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="p-4 sm:p-6 space-y-4">
        @unless ($tracked)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
                Messaging is not set up yet — its tables come with the next migration.
            </div>
        @endunless

        {{-- ════════ WALLETS ════════ --}}
        @if ($tab === 'wallets' && $tracked)
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ([
                    ['In all wallets', $inr($totals['balance']), 'what the schools have left to send with'],
                    ['Recharged this month', $inr($totals['topped']), 'by SuperLMS and by the schools'],
                    ['Messages this month', $inr($totals['used']), 'WhatsApp ' . $inrShort($rates['whatsapp']) . ' · email ' . $inrShort($rates['email']) . ' each'],
                    ['Requests to approve', number_format($totals['pending']), \App\Services\MessagingWallet::prepaid() ? 'Prepaid is on' : 'Prepaid is off'],
                ] as [$tLabel, $tValue, $tSub])
                    <div class="bg-white border border-gray-200 rounded-xl px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $tLabel }}</p>
                        <p class="mt-1 text-lg font-bold text-gray-900 tabular-nums">{{ $tValue }}</p>
                        <p class="text-xs text-gray-400">{{ $tSub }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">School</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3 text-right">Recharged this month</th>
                            <th class="px-4 py-3 text-right">Used this month</th>
                            <th class="px-4 py-3 text-right">Used in all</th>
                            <th class="px-4 py-3 text-left">Last recharge</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($walletRows as $row)
                            <tr wire:key="wallet-{{ $row['id'] }}" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <button wire:click="openStatement({{ $row['id'] }})" class="font-medium text-gray-900 hover:text-blue-600 text-left">{{ $row['name'] }}</button>
                                    @if ($row['pending'])
                                        <span class="ml-1.5 text-[11px] font-semibold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">{{ $row['pending'] }} to approve</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $row['balance'] < 0 ? 'text-red-600' : ($row['balance'] < 100 ? 'text-amber-600' : 'text-gray-900') }}">{{ $inr($row['balance']) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-emerald-600">{{ $row['topped_month'] ? $inr($row['topped_month']) : '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-700">{{ $row['used_month'] ? $inr($row['used_month']) : '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-500">{{ $row['used'] ? $inr($row['used']) : '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $row['last_topup'] ? \Illuminate\Support\Carbon::parse($row['last_topup'])->format('d M Y') : '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                        <button wire:click="openFunds({{ $row['id'] }}, 'credit')" class="px-2.5 py-1 text-xs font-medium text-emerald-700 border border-emerald-200 rounded-md hover:bg-emerald-50">Add funds</button>
                                        <button wire:click="openFunds({{ $row['id'] }}, 'debit')" class="px-2.5 py-1 text-xs font-medium text-gray-600 border border-gray-200 rounded-md hover:bg-gray-50">Take out</button>
                                        <button wire:click="openSchool({{ $row['id'] }}, 'whatsapp')" class="px-2.5 py-1 text-xs font-medium text-blue-600 border border-blue-200 rounded-md hover:bg-blue-50">WhatsApp</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">No school matches.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ════════ REQUESTS ════════ --}}
        @if ($tab === 'requests' && $tracked)
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">To approve</h2>
                    <span class="text-xs text-gray-400">A school's Add funds is put in its wallet once approved.</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($pendingRequests as $req)
                        <div wire:key="req-{{ $req->id }}" class="px-4 py-3 flex flex-wrap items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $req->organization?->name ?? 'School' }} · <span class="text-emerald-600">{{ $inr($req->amount) }}</span></p>
                                <p class="text-xs text-gray-500 break-words">
                                    {{ $req->txn_date?->format('d M Y') }}
                                    @if ($req->mode) · {{ $req->mode }} @endif
                                    @if ($req->reference) · Ref. {{ $req->reference }} @endif
                                    @if ($req->creator) · by {{ $req->creator->name }} @endif
                                </p>
                                @if ($req->note)
                                    <p class="text-xs text-gray-400 break-words">{{ $req->note }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="openReject({{ $req->id }})" class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-md hover:bg-red-50">Reject</button>
                                <button wire:click="approveRequest({{ $req->id }})" wire:confirm="Add {{ $inr($req->amount) }} to {{ $req->organization?->name }}'s wallet?"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-md hover:bg-emerald-700">Approve</button>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-10 text-center text-sm text-gray-400">Nothing to approve.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                <div class="px-4 py-3 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-900">Decided</h2></div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="px-4 py-2 text-left">School</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-left">Paid by</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Decided</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($decidedRequests as $req)
                            <tr wire:key="dec-{{ $req->id }}">
                                <td class="px-4 py-2 text-gray-800">{{ $req->organization?->name ?? 'School' }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ $inr($req->amount) }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ trim(($req->mode ?? '') . ($req->reference ? ' · Ref. ' . $req->reference : '')) ?: '—' }}</td>
                                <td class="px-4 py-2">
                                    <span class="text-xs font-medium {{ $req->status === 'approved' ? 'text-emerald-600' : 'text-red-600' }}">{{ ucfirst($req->status) }}</span>
                                    @if ($req->decision_note)<span class="block text-xs text-gray-400">{{ $req->decision_note }}</span>@endif
                                </td>
                                <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $req->decided_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">None yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ════════ A SCHOOL: pick one first ════════ --}}
        @if (in_array($tab, ['whatsapp', 'sms', 'email'], true) && !$schoolName)
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-14 text-center">
                <p class="text-sm font-semibold text-gray-800">Pick a school</p>
                <p class="text-xs text-gray-400 mt-1">Its {{ \App\Livewire\SuperAdmin\Messaging::TABS[$tab] }} switches and what it sent show here.</p>
            </div>
        @endif

        {{-- ════════ WHATSAPP (was the school view's WhatsApp tab) ════════ --}}
        @if ($tab === 'whatsapp' && $schoolName)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                @if ($wa = $this->whatsAppAnalytics())
                    @include('livewire.partials.whatsapp-analytics', ['wa' => $wa, 'waEditable' => true, 'waKey' => 'sa-messaging-' . $school])
                    <p class="mt-4 text-xs text-gray-400">
                        Notice is the school's own message to many (More → Messaging → Send), on the WhatsApp template
                        <code class="text-gray-500">{{ config('services.whatsapp.templates.notice', 'school_notice') }}</code>.
                    </p>
                @else
                    <p class="text-center py-12 text-sm text-gray-400">WhatsApp messages are not set up yet.</p>
                @endif
            </div>
        @endif

        {{-- ════════ SMS (was the school view's SMS tab) ════════ --}}
        @if ($tab === 'sms' && $schoolName)
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                @if ($sms = $this->smsSummary())
                    <div class="border border-gray-200 rounded-md">
                        <div class="px-4 py-3 flex flex-wrap items-center gap-3">
                            <button type="button" role="switch" aria-checked="{{ $sms['enabled'] ? 'true' : 'false' }}"
                                wire:click="toggleSms" wire:loading.attr="disabled" wire:target="toggleSms"
                                title="{{ $sms['enabled'] ? 'Turn off' : 'Turn on' }}"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-60 {{ $sms['enabled'] ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $sms['enabled'] ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                            </button>
                            <span class="text-sm font-medium text-gray-800">Welcome by SMS</span>
                            <span class="text-xs {{ $sms['enabled'] ? 'text-emerald-600' : 'text-gray-400' }}">{{ $sms['enabled'] ? 'On' : 'Off' }}</span>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-100 space-y-2">
                            <p class="text-xs text-gray-500 leading-relaxed">
                                When on, a new student gets an SMS with the school, class, section, admission number,
                                password and the app link, and a new teacher one with their username, password and the
                                app link — and <strong class="text-gray-700">no welcome email and no WhatsApp</strong>.
                                A changed mobile number and Send Credentials go by SMS too. When off, the email and
                                WhatsApp go as before.
                            </p>
                            @unless ($sms['configured'])
                                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2.5 py-1.5">
                                    Bhash SMS is not set up on the server yet (BHASH_SMS_USER, BHASH_SMS_PASS, BHASH_SMS_SENDER),
                                    so this school still gets the email and WhatsApp.
                                </p>
                            @endunless
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach ([
                            ['Sent this month', number_format($sms['month']['sent']), 'text-gray-900'],
                            ['Failed this month', number_format($sms['month']['failed']), $sms['month']['failed'] ? 'text-red-600' : 'text-gray-900'],
                            ['SMS parts this month', number_format($sms['month']['parts']), 'text-gray-900'],
                            ['Sent in all', number_format($sms['total']), 'text-gray-900'],
                        ] as [$smsLabel, $smsValue, $smsTone])
                            <div class="border border-gray-200 rounded-md px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $smsLabel }}</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums {{ $smsTone }}">{{ $smsValue }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="border border-gray-200 rounded-md overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                <tr>
                                    <th class="px-4 py-2 text-left">When</th>
                                    <th class="px-4 py-2 text-left">To</th>
                                    <th class="px-4 py-2 text-left">Kind</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($sms['messages'] as $smsRow)
                                    <tr wire:key="sms-row-{{ $smsRow['id'] }}">
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-500 tabular-nums">{{ optional($smsRow['at'])->format('d M Y, h:i A') }}</td>
                                        <td class="px-4 py-2">
                                            <span class="text-gray-800">{{ $smsRow['name'] }}</span>
                                            <span class="text-xs text-gray-400"> · {{ $smsRow['who'] }} · {{ $smsRow['to'] }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $smsRow['kind'] }}</td>
                                        <td class="px-4 py-2">
                                            <span class="text-xs font-medium {{ $smsRow['status'] === 'sent' ? 'text-emerald-600' : ($smsRow['status'] === 'failed' ? 'text-red-600' : 'text-gray-500') }}">{{ ucfirst($smsRow['status']) }}</span>
                                            @if ($smsRow['status'] === 'failed' && $smsRow['error'])
                                                <span class="block text-xs text-gray-400 break-words max-w-xs">{{ $smsRow['error'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No SMS sent for this school yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center py-12 text-sm text-gray-400">SMS is not set up yet.</p>
                @endif
            </div>
        @endif

        {{-- ════════ EMAIL ════════ --}}
        @if ($tab === 'email' && $schoolName)
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                @if ($em = $this->emailSummary())
                    <div class="border border-gray-200 rounded-md">
                        <div class="px-4 py-3 flex flex-wrap items-center gap-3">
                            <button type="button" role="switch" aria-checked="{{ $em['enabled'] ? 'true' : 'false' }}"
                                wire:click="toggleEmail" wire:loading.attr="disabled" wire:target="toggleEmail"
                                title="{{ $em['enabled'] ? 'Turn off' : 'Turn on' }}"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-60 {{ $em['enabled'] ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $em['enabled'] ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                            </button>
                            <span class="text-sm font-medium text-gray-800">Email messaging</span>
                            <span class="text-xs {{ $em['enabled'] ? 'text-emerald-600' : 'text-gray-400' }}">{{ $em['enabled'] ? 'On' : 'Off' }}</span>
                            <span class="text-xs text-gray-400">{{ $inrShort($em['rate']) }} an email · not charged when it fails</span>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-100 space-y-2">
                            <p class="text-xs text-gray-500 leading-relaxed">
                                When on, the school can email its students and teachers from More → Messaging → Send, paid from its wallet.
                                The email goes from SuperLMS's address with the school's name as the sender.
                            </p>
                            @unless ($em['configured'])
                                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2.5 py-1.5">ZeptoMail is not set up on the server (ZEPTOMAIL_API_TOKEN), so emails will fail.</p>
                            @endunless
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach ([
                            ['Sent this month', number_format($em['month']['sent']), 'text-gray-900'],
                            ['Failed this month', number_format($em['month']['failed']), $em['month']['failed'] ? 'text-red-600' : 'text-gray-900'],
                            ['Charged this month', $inr($em['month']['amount']), 'text-gray-900'],
                            ['Sent in all', number_format($em['total']), 'text-gray-900'],
                        ] as [$emLabel, $emValue, $emTone])
                            <div class="border border-gray-200 rounded-md px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $emLabel }}</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums {{ $emTone }}">{{ $emValue }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="border border-gray-200 rounded-md overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                <tr>
                                    <th class="px-4 py-2 text-left">When</th>
                                    <th class="px-4 py-2 text-left">To</th>
                                    <th class="px-4 py-2 text-left">Subject</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-right">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($em['messages'] as $emRow)
                                    <tr wire:key="em-row-{{ $emRow['id'] }}">
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-500 tabular-nums">{{ optional($emRow['at'])->format('d M Y, h:i A') }}</td>
                                        <td class="px-4 py-2"><span class="text-gray-800">{{ $emRow['name'] }}</span> <span class="text-xs text-gray-400">· {{ $emRow['to'] }}</span></td>
                                        <td class="px-4 py-2 text-gray-600 break-words">{{ $emRow['subject'] }}</td>
                                        <td class="px-4 py-2">
                                            <span class="text-xs font-medium {{ $emRow['status'] === 'sent' ? 'text-emerald-600' : ($emRow['status'] === 'failed' ? 'text-red-600' : 'text-gray-500') }}">{{ ucfirst($emRow['status']) }}</span>
                                            @if ($emRow['status'] === 'failed' && $emRow['error'])<span class="block text-xs text-gray-400 break-words max-w-xs">{{ $emRow['error'] }}</span>@endif
                                        </td>
                                        <td class="px-4 py-2 text-right tabular-nums text-gray-600">{{ is_numeric($emRow['charge']) ? $inr($emRow['charge']) : ($emRow['charge'] === 'free' ? 'Free' : 'Not charged') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">No email sent for this school yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center py-12 text-sm text-gray-400">Email messaging is not set up yet.</p>
                @endif
            </div>
        @endif

        {{-- ════════ SENT (messages schools sent to many) ════════ --}}
        @if ($tab === 'sent' && $tracked)
            @include('livewire.partials.messaging-campaigns', ['campaigns' => $campaigns, 'showSchool' => $school === ''])
        @endif

        {{-- ════════ SETTINGS ════════ --}}
        @if ($tab === 'settings' && $tracked && $canSettings)
            <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 max-w-3xl">
                <div class="p-5 grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">WhatsApp — a message (₹)</label>
                        <input wire:model="setWhatsappRate" type="number" step="0.01" min="0" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-400">Absent alert, Fee received, Send Credentials and Notice. The welcome stays free.</p>
                        @error('setWhatsappRate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email — an email (₹)</label>
                        <input wire:model="setEmailRate" type="number" step="0.01" min="0" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-400">Each email a school sends from More → Messaging.</p>
                        @error('setEmailRate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="p-5">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="setPrepaid" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">Prepaid — send only what the wallet can pay for</span>
                            <span class="block text-xs text-gray-500 mt-0.5">On: a charged WhatsApp or email is not sent when the school's wallet is short (the school sees it on its Messaging page). Off: messages always go and the wallet may go below zero, to be paid later.</span>
                        </span>
                    </label>
                </div>
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Where schools pay</label>
                    <textarea wire:model="setPaymentDetails" rows="4" placeholder="UPI: superlms@bank&#10;A/c 1234567890 · IFSC ABCD0001234 · SuperLMS" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    <p class="mt-1 text-xs text-gray-400">Shown to a school on Add funds. It pays here, then asks for the money to be put in its wallet; you approve it in Requests.</p>
                    @error('setPaymentDetails')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="px-5 py-3 flex justify-end">
                    <button wire:click="saveSettings" wire:loading.attr="disabled" wire:target="saveSettings"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                        <span wire:loading wire:target="saveSettings">Saving…</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════ ADD FUNDS / TAKE OUT SLIDE-IN ══════════ --}}
    @if ($showFunds)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeFunds"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $fundsType === 'debit' ? 'Take Out of a Wallet' : 'Add Funds' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $fundsType === 'debit' ? 'Money taken out of a school\'s messaging wallet' : 'Money put in a school\'s messaging wallet' }}</p>
                    </div>
                    <button wire:click="closeFunds" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (['credit' => 'Add funds', 'debit' => 'Take out'] as $value => $label)
                            <button type="button" wire:click="$set('fundsType', '{{ $value }}')"
                                class="px-3.5 py-3 text-left border-2 rounded-md transition-all {{ $fundsType === $value ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:bg-gray-50' }}">
                                <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">School <span class="text-red-500">*</span></label>
                        <select wire:model.live="fundsOrgId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pick a school…</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @if ($fundsOrgId !== '')
                            <p class="mt-1 text-xs text-gray-500">Balance now: <strong>{{ $inr(\App\Services\MessagingWallet::balance((int) $fundsOrgId)) }}</strong></p>
                        @endif
                        @error('fundsOrgId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                            <input wire:model="fundsAmount" type="number" step="0.01" min="1" placeholder="0.00" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fundsAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input wire:model="fundsDate" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fundsDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mode</label>
                            <select wire:model.live="fundsMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @foreach (\App\Services\MessagingWallet::MODES as $m)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Reference</label>
                            <input wire:model="fundsReference" type="text" placeholder="UTR / transaction id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fundsReference')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Note</label>
                        <textarea wire:model="fundsNote" rows="2" placeholder="What is it for?" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('fundsNote')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    @if ($fundsType === 'credit')
                        <label class="flex items-start gap-3 cursor-pointer border border-gray-200 rounded-md px-3.5 py-3">
                            <input type="checkbox" wire:model="fundsInLedger" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-medium text-gray-800">Money received from the school</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Shows as a credit in the Ledger. Leave it off for a free credit.</span>
                            </span>
                        </label>
                    @endif
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeFunds" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveFunds" wire:loading.attr="disabled" wire:target="saveFunds"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveFunds">{{ $fundsType === 'debit' ? 'Take Out' : 'Add Funds' }}</span>
                        <span wire:loading wire:target="saveFunds">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ REJECT POPUP ══════════ --}}
    @if ($rejectId)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4 lms-cover" wire:click.self="closeReject">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-sm font-semibold text-gray-900">Reject this request?</h3>
                <p class="text-xs text-gray-400 mt-0.5">The school sees the reason on its Messaging page.</p>
                <textarea wire:model="rejectReason" rows="3" placeholder="Payment not received…" class="mt-4 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                @error('rejectReason')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                <div class="mt-4 flex items-center gap-2">
                    <button wire:click="closeReject" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg">Cancel</button>
                    <button wire:click="confirmReject" class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">Reject</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ A SCHOOL'S WALLET STATEMENT ══════════ --}}
    @if ($statementOrgId && isset($statement))
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeStatement"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $statement['school'] }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Messaging wallet · balance <strong class="{{ $statement['balance'] < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $inr($statement['balance']) }}</strong></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="openFunds({{ $statementOrgId }}, 'credit')" class="px-3 py-1.5 text-xs font-medium text-emerald-700 border border-emerald-200 rounded-md hover:bg-emerald-50">Add funds</button>
                        <button wire:click="closeStatement" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-5">
                    @include('livewire.partials.messaging-wallet-statement', ['rows' => $statement['rows']])
                </div>
            </div>
        </div>
    @endif
</div>
