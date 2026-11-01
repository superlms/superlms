<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>
    @php
        $inr      = fn ($v) => '₹' . number_format((float) $v, 2);
        $inrShort = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
        $anyOn    = $channels['whatsapp']['on'] || $channels['email']['on'];
        $low      = $prepaid && $balance < max($channels['whatsapp']['rate'], $channels['email']['rate']);
    @endphp

    {{-- ══════════ HEADER + TABS ══════════ --}}
    <div class="sticky top-0 z-30">
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Messaging</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200 mr-1">
                        <span class="pr-4">Balance: <strong class="{{ $balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $inr($balance) }}</strong></span>
                        <span class="px-4">Used this month: <strong class="text-blue-600">{{ $inr($month['total'] ?? 0) }}</strong></span>
                        <span class="pl-4">WhatsApp {{ $inrShort($channels['whatsapp']['rate']) }} · Email {{ $inrShort($channels['email']['rate']) }} each</span>
                    </div>
                    @if ($tracked)
                        <button wire:click="openFunds"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add Funds
                        </button>
                    @endif
                </div>
            </div>
            <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Balance: <strong class="{{ $balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $inr($balance) }}</strong></span>
                <span>Used this month: <strong class="text-blue-600">{{ $inr($month['total'] ?? 0) }}</strong></span>
            </div>
        </div>
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
            <nav class="flex gap-1 overflow-x-auto">
                @foreach (\App\Livewire\Admin\Messaging::TABS as $key => $label)
                    <button wire:click="setTab('{{ $key }}')"
                        class="py-3 px-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                            {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4">
        @unless ($tracked)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">Messaging is being set up. Please check back soon.</div>
        @endunless

        @if ($low)
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                Your wallet is low: paid WhatsApp messages (absent alerts, fee received …) and emails are not sent until you add funds.
            </div>
        @endif

        {{-- ════════ WALLET ════════ --}}
        @if ($tab === 'wallet' && $tracked)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-gradient-to-br from-indigo-600 to-blue-600 text-white rounded-xl px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-white/70">Wallet balance</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">{{ $inr($balance) }}</p>
                    <p class="text-xs text-white/70 mt-0.5">{{ $prepaid ? 'Paid messages go while there is money in it' : 'Paid messages are taken from it' }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Used this month</p>
                    <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ $inr($month['total'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ number_format($month['whatsapp_count'] ?? 0) }} WhatsApp · {{ number_format($month['email_count'] ?? 0) }} email</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Waiting for approval</p>
                    <p class="mt-1 text-xl font-bold tabular-nums {{ $pending > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $inr($pending) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Added by you, approved by SuperLMS</p>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-900">Statement</h2>
                    <p class="text-xs text-gray-400">WhatsApp {{ $inrShort($channels['whatsapp']['rate']) }} · Email {{ $inrShort($channels['email']['rate']) }} a message · a message not delivered is not charged</p>
                </div>
                @include('livewire.partials.messaging-wallet-statement', ['rows' => $rows])
            </div>
        @endif

        {{-- ════════ SEND ════════ --}}
        @if ($tab === 'send')
            @if (!$anyOn)
                <div class="bg-white rounded-xl border border-gray-200 px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-gray-800">Messaging is not on for your school yet</p>
                    <p class="text-xs text-gray-400 mt-1">Ask SuperLMS to turn on WhatsApp or email messaging (More → Contact Admin).</p>
                </div>
            @else
                <div class="grid lg:grid-cols-3 gap-4 items-start">
                    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Send by <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($channels as $key => $ch)
                                    <button type="button" @if ($ch['on']) wire:click="$set('channel', '{{ $key }}')" @endif @disabled(!$ch['on'])
                                        class="px-3.5 py-3 text-left border-2 rounded-md transition-all disabled:opacity-50 disabled:cursor-not-allowed
                                            {{ $channel === $key ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:bg-gray-50' }}">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $ch['label'] }}</span>
                                        <span class="block text-xs text-gray-500">{{ $ch['on'] ? $inrShort($ch['rate']) . ' a message' : 'Not on for your school' }}</span>
                                    </button>
                                @endforeach
                            </div>
                            @error('channel')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">To <span class="text-red-500">*</span></label>
                                <select wire:model.live="audience" class="w-full px-3 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    @foreach (\App\Support\MessagingAudience::AUDIENCES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('audience')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            @if ($audience === 'students')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Class</label>
                                    <select wire:model.live="standardId" class="w-full px-3 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All classes</option>
                                        @foreach ($standards as $std)
                                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('standardId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                                    <select wire:model.live="sectionId" @disabled($standardId === '') class="w-full px-3 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50">
                                        <option value="">All sections</option>
                                        @foreach ($sections as $sec)
                                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('sectionId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            @endif
                        </div>

                        @if ($channel === 'email')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                                <input wire:model="subject" type="text" maxlength="150" placeholder="Holiday on Monday" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('subject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        <div x-data="{ len: @js(mb_strlen($body)) }">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Message <span class="text-red-500">*</span></label>
                            <textarea wire:model="body" @input="len = $event.target.value.length" rows="6" maxlength="{{ \App\Livewire\Admin\Messaging::MAX[$channel] ?? 900 }}"
                                placeholder="Dear parents, the school will remain closed on Monday for…"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            <div class="flex justify-between mt-1">
                                @error('body')<p class="text-xs text-red-500">{{ $message }}</p>@else<span class="text-xs text-gray-400">{{ $channel === 'whatsapp' ? 'Goes as one paragraph on WhatsApp, with your school\'s name.' : 'Goes with your school\'s name as the sender.' }}</span>@enderror
                                <span class="text-xs text-gray-400 tabular-nums"><span x-text="len"></span>/{{ \App\Livewire\Admin\Messaging::MAX[$channel] ?? 900 }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                            @if ($confirming)
                                <button wire:click="cancelReview" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Back</button>
                                <button wire:click="send" wire:loading.attr="disabled" wire:target="send"
                                    class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md disabled:opacity-60">
                                    <span wire:loading.remove wire:target="send">Send to {{ number_format($preview['reachable']) }} · {{ $inr($preview['cost']) }}</span>
                                    <span wire:loading wire:target="send">Sending…</span>
                                </button>
                            @else
                                <button wire:click="review" wire:loading.attr="disabled" wire:target="review"
                                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">Review &amp; Send</button>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <h2 class="text-sm font-semibold text-gray-900">Summary</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">To</dt><dd class="font-medium text-gray-900 text-right">{{ $preview['label'] }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">People</dt><dd class="font-medium text-gray-900 tabular-nums">{{ number_format($preview['total']) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">With {{ $channel === 'email' ? 'an email' : 'a mobile number' }}</dt><dd class="font-medium text-gray-900 tabular-nums">{{ number_format($preview['reachable']) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Rate</dt><dd class="font-medium text-gray-900 tabular-nums">{{ $inrShort($preview['rate']) }}</dd></div>
                            <div class="flex justify-between gap-3 pt-2 border-t border-gray-100"><dt class="text-gray-700 font-medium">Costs about</dt><dd class="font-bold text-gray-900 tabular-nums">{{ $inr($preview['cost']) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Wallet</dt><dd class="font-medium tabular-nums {{ $balance < $preview['cost'] ? 'text-red-600' : 'text-emerald-600' }}">{{ $inr($balance) }}</dd></div>
                        </dl>
                        @if ($confirming)
                            <p class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-2.5 py-1.5">Ready — press Send to send it.</p>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- ════════ SENT ════════ --}}
        @if ($tab === 'sent' && $tracked)
            @include('livewire.partials.messaging-campaigns', ['campaigns' => $campaigns, 'showSchool' => false])
        @endif

        {{-- ════════ USAGE ════════ --}}
        @if ($tab === 'usage')
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">WhatsApp</h2>
                @if ($wa = $this->whatsAppAnalytics())
                    @include('livewire.partials.whatsapp-analytics', ['wa' => $wa, 'waEditable' => false, 'waKey' => 'admin-messaging-' . auth()->user()->organization_id])
                @else
                    <p class="text-center py-10 text-sm text-gray-400">No WhatsApp messages yet.</p>
                @endif
            </div>

            @if ($email)
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-gray-900">Email</h2>
                        <span class="text-xs text-gray-500">This month: {{ number_format($email['month']['sent']) }} sent · {{ number_format($email['month']['failed']) }} failed · {{ $inr($email['month']['amount']) }}</span>
                    </div>
                    <div class="border border-gray-200 rounded-md overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                <tr><th class="px-4 py-2 text-left">When</th><th class="px-4 py-2 text-left">To</th><th class="px-4 py-2 text-left">Subject</th><th class="px-4 py-2 text-left">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($email['messages'] as $emRow)
                                    <tr wire:key="adm-em-{{ $emRow['id'] }}">
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-500 tabular-nums">{{ optional($emRow['at'])->format('d M Y, h:i A') }}</td>
                                        <td class="px-4 py-2"><span class="text-gray-800">{{ $emRow['name'] }}</span> <span class="text-xs text-gray-400">· {{ $emRow['to'] }}</span></td>
                                        <td class="px-4 py-2 text-gray-600 break-words">{{ $emRow['subject'] }}</td>
                                        <td class="px-4 py-2"><span class="text-xs font-medium {{ $emRow['status'] === 'sent' ? 'text-emerald-600' : ($emRow['status'] === 'failed' ? 'text-red-600' : 'text-gray-500') }}">{{ ucfirst($emRow['status']) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No email sent yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- ══════════ ADD FUNDS SLIDE-IN ══════════ --}}
    @if ($showFunds)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeFunds"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add Funds</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pay SuperLMS, then tell us here — it is put in your wallet once approved.</p>
                    </div>
                    <button wire:click="closeFunds" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @if ($details = \App\Services\MessagingWallet::paymentDetails())
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Pay to</p>
                            <p class="mt-1 text-sm text-blue-900 whitespace-pre-line break-words">{{ $details }}</p>
                        </div>
                    @else
                        <p class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md px-3 py-2.5">Ask SuperLMS where to pay (More → Contact Admin), then add the payment here.</p>
                    @endif
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount paid (₹) <span class="text-red-500">*</span></label>
                            <input wire:model="fAmount" type="number" step="0.01" min="1" placeholder="0.00" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date paid <span class="text-red-500">*</span></label>
                            <input wire:model="fDate" type="date" max="{{ now()->toDateString() }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Paid by <span class="text-red-500">*</span></label>
                            <select wire:model="fMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @foreach (['UPI', 'Bank Transfer', 'Cheque', 'Cash', 'Card', 'Other'] as $m)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endforeach
                            </select>
                            @error('fMode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Reference / UTR <span class="text-red-500">*</span></label>
                            <input wire:model="fReference" type="text" placeholder="Transaction id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fReference')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Note</label>
                        <textarea wire:model="fNote" rows="2" placeholder="Anything SuperLMS should know" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('fNote')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeFunds" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="requestFunds" wire:loading.attr="disabled" wire:target="requestFunds"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="requestFunds">Send for Approval</span>
                        <span wire:loading wire:target="requestFunds">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
