<div class="sa-skin sa-home min-h-screen">
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')
    @include('livewire.partials.messaging-styles')

    {{-- More → Messaging in the Home's analytics look (2026-11-21): the
         school's WhatsApp, SMS and email messages — how many are left, buying
         more (UPI QR or bank, approved by SuperLMS), sending to many, and the
         packs with what was paid. --}}
    @php
        $C      = \App\Services\MessagePacks::CHANNELS;
        $icons  = ['whatsapp' => 'chat-bubble-left-right', 'sms' => 'device-phone-mobile', 'email' => 'envelope'];
        $colors = ['whatsapp' => '#0d9488', 'sms' => '#0ea5e9', 'email' => '#f59e0b'];
        $num    = fn ($v) => number_format((int) $v);
        $inr    = fn ($v) => '₹' . number_format((float) $v, 2);
        $rate   = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
        $anyOn  = collect($channels)->contains(fn ($c) => $c['on']);
        $out    = $prepaid ? collect($channels)->filter(fn ($c, $k) => $c['on'] && $c['left'] <= 0) : collect();
        $chipIcons = ['overview' => 'chart-bar', 'buy' => 'banknotes', 'send' => 'paper-airplane', 'sent' => 'inbox-stack', 'usage' => 'chart-bar', 'history' => 'receipt-percent'];
        $packState = function ($st, $pk) use ($inr) {
            if ($pk->status === 'pending') return ['Waiting for approval', 'amber'];
            if ($pk->status === 'rejected') return ['Rejected', 'rose'];
            if ($pk->type === 'take') return ['Taken back', ''];
            return match ($st['state'] ?? 'free') {
                'paid'  => ['Paid', 'green'],
                'part'  => ['Part paid · ' . $inr($st['paid']), 'amber'],
                'due'   => ['To pay', 'rose'],
                default => ['Free', 'teal'],
            };
        };
    @endphp

    {{-- ══════════ HEADER ══════════ --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>Messaging</h1>
            <p class="hidden sm:block">Your WhatsApp, SMS and email messages</p>
        </div>
        <div class="sk-right">
            @if ($tracked)
                <span class="hidden md:inline-flex"><span class="sk-pill live">{{ $num(collect($channels)->sum(fn ($c) => max(0, $c['left']))) }} left</span></span>
                <button type="button" wire:click="setTab('send')" class="sk-pill" style="height: 36px; padding: 0 14px; font-size: 13.5px">
                    <x-icon name="paper-airplane" class="w-4 h-4" /> Send
                </button>
                <button type="button" wire:click="buy" class="sk-cta" data-buy-messages>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Buy Messages
                </button>
            @endif
        </div>
    </div>

    <nav class="an-chips" aria-label="Messaging sections">
        @foreach (\App\Livewire\Admin\Messaging::TABS as $key => $label)
            <button type="button" wire:click="setTab('{{ $key }}')" class="an-chip {{ $tab === $key ? 'on' : '' }}" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}" data-mg-chip="{{ $key }}">
                <x-icon name="{{ $chipIcons[$key] }}" /> {{ $label }}
                @if ($key === 'history' && $pending->count())<span class="n">{{ $pending->count() }}</span>@endif
            </button>
        @endforeach
    </nav>

    <div class="p-4 sm:p-6 pt-4 sm:pt-5 space-y-5">
        @unless ($tracked)
            <div class="sa-card px-6 py-14 text-center text-sm text-gray-500">Messaging is being set up. Please check back soon.</div>
        @endunless

        @if ($tracked && $out->count())
            <div class="mg-warn" data-out-note>
                <x-icon name="exclamation-triangle" />
                <span>You have no {{ $out->pluck('label')->implode(' / ') }} messages left: those messages (absent alerts, fee received, notices …) are not sent until you <button type="button" wire:click="buy('{{ $out->keys()->first() }}')" class="font-semibold underline">buy messages</button>.</span>
            </div>
        @endif

        {{-- ════════════════════ OVERVIEW ════════════════════ --}}
        @if ($tab === 'overview' && $tracked)
            <div class="mg-chs" data-mg-left>
                @foreach ($C as $ch => $label)
                    @php $c = $channels[$ch]; $g = $given[$ch] ?? 0; $used = max(0, $g - $c['left']); $pct = $g > 0 ? min(100, round($used / $g * 100)) : ($c['left'] < 0 ? 100 : 0); @endphp
                    <div class="sa-card p-5">
                        <div class="flex items-center gap-3">
                            <span class="mg-ch-ic {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $label }}</p>
                                <p class="text-xs text-gray-400">{{ $rate($c['rate']) }} a message{{ $ch === 'sms' ? ' part' : '' }}</p>
                            </div>
                            <span class="ml-auto an-tag {{ $c['on'] ? 'green' : '' }}" title="{{ $c['on'] ? 'You can send notices on it' : 'SuperLMS turns it on' }}">{{ $c['on'] ? 'Notices on' : 'Notices off' }}</span>
                        </div>
                        <p class="sd-big mt-5 {{ $c['left'] < 0 ? 'text-rose-600' : '' }}">{{ $num($c['left']) }}</p>
                        <p class="text-xs text-gray-400 mt-1.5">left of {{ $num($g) }} · {{ $num($month[$ch]) }} sent this month</p>
                        <div class="mg-meter {{ $ch }} mt-4"><i class="{{ $c['left'] < 0 ? 'over' : '' }}" style="width: {{ $pct }}%"></i></div>
                        <div class="sd-foot flex items-center justify-between gap-2">
                            <span>{{ $g > 0 && $c['left'] < \App\Services\MessagePacks::LOW ? 'Running low' : ($g ? $pct . '% used' : 'No messages yet') }}</span>
                            <button type="button" wire:click="buy('{{ $ch }}')" class="mg-btn line sm">Buy {{ $label }}</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <dl class="sd-figs" style="--c: 4" data-mg-money>
                <div><dt>Sent this month</dt><dd>{{ $num(array_sum($month)) }}</dd></div>
                <div><dt>Waiting for approval</dt><dd>{{ $num($pending->sum('messages')) }} messages</dd></div>
                <div><dt>Paid for messages</dt><dd class="up">{{ $inr($money['paid']) }}</dd></div>
                <div><dt>To pay SuperLMS</dt><dd class="{{ $money['due'] > 0 ? 'down' : '' }}">{{ $inr($money['due']) }}</dd></div>
            </dl>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card p-5 sm:p-6">
                    <h3 class="sa-card-title">Sent by Month</h3>
                    <p class="sa-card-sub">Charged WhatsApp, SMS parts and emails</p>
                    @include('livewire.super-admin.partials.month-bars', ['values' => $monthly['total'], 'labels' => $monthly['labels'], 'empty' => 'No messages sent yet'])
                    <table class="an-cmp mt-4">
                        <thead><tr><th>Channel</th>@foreach (array_slice($monthly['labels'], -3) as $l)<th>{{ $l }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($C as $ch => $label)
                                <tr><td><span class="mg-dot mr-2" style="background: {{ $colors[$ch] }}"></span>{{ $label }}</td>@foreach (array_slice($monthly[$ch], -3) as $v)<td>{{ $num($v) }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sa-card p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="sa-card-title">Latest Packs</h3>
                            <p class="sa-card-sub">Messages you bought, or SuperLMS gave you</p>
                        </div>
                        <button type="button" wire:click="setTab('history')" class="sk-pill">All <x-icon name="arrow-right" class="w-3.5 h-3.5" /></button>
                    </div>
                    <div class="mt-3">
                        @forelse ($recent as $pk)
                            @php [$stLabel, $stTone] = $packState($recentState[$pk->id] ?? null, $pk); @endphp
                            <div class="an-row" wire:key="rc-{{ $pk->id }}">
                                <span class="mg-ch-ic sm {{ $pk->channel }}"><x-icon name="{{ $icons[$pk->channel] ?? 'envelope' }}" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">{{ $pk->type === 'take' ? '−' : '+' }}{{ $num($pk->messages) }} {{ \App\Services\MessagePacks::label($pk->channel) }}</p>
                                    <p class="text-xs text-gray-400">{{ $pk->txn_date?->format('d M Y') }} · {{ $pk->source === 'school' ? 'Bought by you' : ($pk->source === 'carried' ? 'From your wallet' : 'From SuperLMS') }}</p>
                                </div>
                                <span class="an-tag {{ $stTone }}">{{ $stLabel }}</span>
                            </div>
                        @empty
                            <div class="sk-empty">
                                No messages yet.
                                <div class="mt-3"><button type="button" wire:click="buy" class="mg-btn main sm">Buy Messages</button></div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- ════════════════════ BUY MESSAGES ════════════════════ --}}
        @if ($tab === 'buy' && $tracked)
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start" data-buy>
                <div class="lg:col-span-3 space-y-3">
                    <div class="mg-box">
                        <p class="mg-step"><b>1</b> Channel</p>
                        <div class="mg-opts">
                            @foreach ($C as $ch => $label)
                                <button type="button" wire:click="$set('bChannel', '{{ $ch }}')" class="mg-opt {{ $bChannel === $ch ? 'on' : '' }}" data-opt-channel="{{ $ch }}">
                                    <span class="tick"></span>
                                    <span class="mg-ch-ic sm {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                                    <b>{{ $label }}</b>
                                    <small>{{ $rate($channels[$ch]['rate']) }} a message{{ $ch === 'sms' ? ' part' : '' }}<br>{{ $num($channels[$ch]['left']) }} left</small>
                                </button>
                            @endforeach
                        </div>
                        @error('bChannel')<p class="mg-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="mg-box">
                        <p class="mg-step"><b>2</b> How many messages</p>
                        <div class="mg-sizes mb-3">
                            @foreach (\App\Services\MessagePacks::SIZES as $size)
                                <button type="button" wire:click="pickSize({{ $size }})" class="mg-size {{ (int) $bMessages === $size ? 'on' : '' }}">{{ $num($size) }}</button>
                            @endforeach
                        </div>
                        <input wire:model.live.debounce.300ms="bMessages" type="number" min="1" step="1" placeholder="Or type any number" class="mg-in @error('bMessages') err @enderror" aria-label="Messages">
                        @error('bMessages')<p class="mg-err">{{ $message }}</p>@enderror
                        <div class="mg-total mt-4">
                            <div>
                                <p class="text-xs font-semibold text-teal-700 uppercase tracking-wider">To pay</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $num((int) $bMessages) }} {{ $C[$bChannel] }} × {{ $rate($channels[$bChannel]['rate']) }}</p>
                            </div>
                            <p class="sd-big" data-buy-total>{{ $inr($buyTotal) }}</p>
                        </div>
                    </div>

                    <div class="mg-box">
                        <p class="mg-step"><b>3</b> After you pay</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mg-label" for="b-mode">Paid by</label>
                                <select id="b-mode" wire:model="bMode" class="mg-in">
                                    @foreach (\App\Services\MessagePacks::MODES as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                </select>
                                @error('bMode')<p class="mg-err">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mg-label" for="b-date">Date paid</label>
                                <input id="b-date" wire:model="bDate" type="date" max="{{ now()->toDateString() }}" class="mg-in @error('bDate') err @enderror">
                                @error('bDate')<p class="mg-err">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="mg-label" for="b-ref">Reference / UTR</label>
                            <input id="b-ref" wire:model="bReference" type="text" placeholder="The UPI / bank transaction id" class="mg-in @error('bReference') err @enderror">
                            @error('bReference')<p class="mg-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="mt-3">
                            <label class="mg-label" for="b-note">Note</label>
                            <textarea id="b-note" wire:model="bNote" rows="2" placeholder="Anything SuperLMS should know (optional)" class="mg-in @error('bNote') err @enderror"></textarea>
                            @error('bNote')<p class="mg-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-gray-500 max-w-sm">SuperLMS checks the payment and approves it; the {{ $num((int) $bMessages) }} {{ $C[$bChannel] }} messages then come into your account.</p>
                            <button type="button" wire:click="requestBuy" wire:loading.attr="disabled" wire:target="requestBuy" class="mg-btn main" data-buy-send>
                                <span wire:loading.remove wire:target="requestBuy">Send for Approval</span>
                                <span wire:loading wire:target="requestBuy">Sending…</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-3">
                    <div class="sa-card p-5" data-pay-to>
                        <h3 class="sa-card-title">Pay {{ $inr($buyTotal) }}</h3>
                        <p class="sa-card-sub">to SuperLMS, then fill in step 3</p>
                        @if ($qr)
                            <div class="mt-4 flex flex-col items-center gap-3">
                                <div class="mg-qr">{!! $qr !!}</div>
                                <p class="text-xs text-gray-500 text-center">Scan with any UPI app — the amount is filled in.<br>UPI id <b class="text-gray-800 select-all">{{ $upiId }}</b> · {{ $upiName }}</p>
                                @if ($upiLink)
                                    <div class="lg:hidden"><a href="{{ $upiLink }}" class="mg-btn line sm">Open UPI app</a></div>
                                @endif
                            </div>
                        @endif
                        @if ($details)
                            <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $qr ? 'Or pay to' : 'Pay to' }}</p>
                                <p class="mt-1 text-sm text-gray-800 whitespace-pre-line break-words">{{ $details }}</p>
                            </div>
                        @endif
                        @if (!$qr && !$details)
                            <p class="mt-4 text-sm text-gray-500">Ask SuperLMS where to pay (More → Contact Admin), then fill in step 3.</p>
                        @endif
                    </div>
                    @if ($pending->count())
                        <div class="sa-card p-5">
                            <h3 class="sa-card-title">Waiting for Approval</h3>
                            <div class="mt-2">
                                @foreach ($pending as $pk)
                                    <div class="an-row" wire:key="bp-{{ $pk->id }}">
                                        <span class="mg-ch-ic sm {{ $pk->channel }}"><x-icon name="{{ $icons[$pk->channel] ?? 'envelope' }}" /></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900">{{ $num($pk->messages) }} {{ \App\Services\MessagePacks::label($pk->channel) }}</p>
                                            <p class="text-xs text-gray-400">{{ $inr($pk->amount) }} · {{ $pk->txn_date?->format('d M Y') }}{{ $pk->reference ? ' · Ref. ' . $pk->reference : '' }}</p>
                                        </div>
                                        <span class="an-tag amber">Waiting</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ════════════════════ SEND ════════════════════ --}}
        @if ($tab === 'send')
            @if (!$anyOn)
                <div class="sa-card px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-gray-800">Messaging is not on for your school yet</p>
                    <p class="text-xs text-gray-400 mt-1">Ask SuperLMS to turn on WhatsApp, SMS or email messaging (More → Contact Admin).</p>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start" data-send>
                    <div class="lg:col-span-2 space-y-3">
                        <div class="mg-box">
                            <p class="mg-step"><b>1</b> Send by</p>
                            <div class="mg-opts">
                                @foreach ($channels as $key => $ch)
                                    <button type="button" @if ($ch['on']) wire:click="$set('channel', '{{ $key }}')" @endif @disabled(!$ch['on']) class="mg-opt {{ $channel === $key ? 'on' : '' }}" data-send-channel="{{ $key }}">
                                        <span class="tick"></span>
                                        <span class="mg-ch-ic sm {{ $key }}"><x-icon name="{{ $icons[$key] }}" /></span>
                                        <b>{{ $ch['label'] }}</b>
                                        <small>{{ $ch['on'] ? $num($ch['left']) . ' left' : 'Not on for your school' }}</small>
                                    </button>
                                @endforeach
                            </div>
                            @error('channel')<p class="mg-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="mg-box">
                            <p class="mg-step"><b>2</b> To</p>
                            <div class="grid sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="mg-label" for="s-aud">Who</label>
                                    <select id="s-aud" wire:model.live="audience" class="mg-in">
                                        @foreach (\App\Support\MessagingAudience::AUDIENCES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                    </select>
                                    @error('audience')<p class="mg-err">{{ $message }}</p>@enderror
                                </div>
                                @if ($audience === 'students')
                                    <div>
                                        <label class="mg-label" for="s-class">Class</label>
                                        <select id="s-class" wire:model.live="standardId" class="mg-in">
                                            <option value="">All classes</option>
                                            @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                                        </select>
                                        @error('standardId')<p class="mg-err">{{ $message }}</p>@enderror
                                    </div>
                                    <div data-sole-section-wrap>
                                        <label class="mg-label" for="s-sec">Section</label>
                                        <select id="s-sec" data-sole-section="keep-all" wire:model.live="sectionId" @disabled($standardId === '') class="mg-in">
                                            <option value="">All sections</option>
                                            @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                                        </select>
                                        @error('sectionId')<p class="mg-err">{{ $message }}</p>@enderror
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mg-box">
                            <p class="mg-step"><b>3</b> Message</p>
                            @if ($channel === 'email')
                                <div class="mb-3">
                                    <label class="mg-label" for="s-sub">Subject</label>
                                    <input id="s-sub" wire:model="subject" type="text" maxlength="150" placeholder="Holiday on Monday" class="mg-in @error('subject') err @enderror">
                                    @error('subject')<p class="mg-err">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            <div x-data="{ len: @js(mb_strlen($body)) }">
                                @if ($channel === 'sms')
                                    <textarea wire:model.live.debounce.500ms="body" @input="len = $event.target.value.length" rows="3" maxlength="{{ \App\Livewire\Admin\Messaging::MAX['sms'] }}"
                                        placeholder="School closed on Monday for Diwali. Classes resume Tuesday." class="mg-in @error('body') err @enderror" aria-label="Message"></textarea>
                                @else
                                    <textarea wire:model="body" @input="len = $event.target.value.length" rows="6" maxlength="{{ \App\Livewire\Admin\Messaging::MAX[$channel] ?? 900 }}"
                                        placeholder="Dear parents, the school will remain closed on Monday for…" class="mg-in @error('body') err @enderror" aria-label="Message"></textarea>
                                @endif
                                <div class="flex justify-between gap-3 mt-1.5">
                                    @error('body')<p class="mg-err" style="margin-top: 0">{{ $message }}</p>@else<span class="text-xs text-gray-400">{{ match ($channel) { 'whatsapp' => 'Goes as one paragraph on WhatsApp, with your school\'s name.', 'sms' => 'Goes as "Notice from your school: …" — short and plain, no line breaks.', default => 'Goes with your school\'s name as the sender.' } }}</span>@enderror
                                    <span class="text-xs text-gray-400 tabular-nums whitespace-nowrap"><span x-text="len"></span>/{{ \App\Livewire\Admin\Messaging::MAX[$channel] ?? 900 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sa-card p-5 space-y-3">
                        <h3 class="sa-card-title">Summary</h3>
                        <dl class="space-y-2.5 text-sm" data-send-summary>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">To</dt><dd class="font-medium text-gray-900 text-right">{{ $preview['label'] }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">People</dt><dd class="font-medium text-gray-900 tabular-nums">{{ $num($preview['total']) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">With {{ $channel === 'email' ? 'an email' : 'a mobile number' }}</dt><dd class="font-medium text-gray-900 tabular-nums">{{ $num($preview['reachable']) }}</dd></div>
                            @if ($channel === 'sms')
                                <div class="flex justify-between gap-3"><dt class="text-gray-500">SMS parts each</dt><dd class="font-medium text-gray-900 tabular-nums">{{ $preview['each'] }}</dd></div>
                            @endif
                            <div class="flex justify-between gap-3 pt-2.5 border-t border-gray-100"><dt class="text-gray-800 font-semibold">Takes</dt><dd class="font-bold text-gray-900 tabular-nums">{{ $num($preview['messages']) }} {{ $C[$channel] }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Left now</dt><dd class="font-semibold tabular-nums {{ ($channels[$channel]['left'] ?? 0) < $preview['messages'] ? 'text-rose-600' : 'text-emerald-700' }}">{{ $num($channels[$channel]['left'] ?? 0) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Worth</dt><dd class="text-gray-500 tabular-nums">{{ $inr($preview['cost']) }}</dd></div>
                        </dl>
                        @if (($channels[$channel]['left'] ?? 0) < $preview['messages'])
                            <div class="mg-warn"><x-icon name="exclamation-triangle" /><span>{{ $prepaid ? 'Not enough messages left —' : 'More than you have left —' }} <button type="button" wire:click="buy('{{ $channel }}')" class="font-semibold underline">buy {{ $C[$channel] }} messages</button>.</span></div>
                        @endif
                        <div class="pt-1 flex items-center gap-2">
                            @if ($confirming)
                                <button type="button" wire:click="cancelReview" class="mg-btn line flex-1">Back</button>
                                <button type="button" wire:click="send" wire:loading.attr="disabled" wire:target="send" class="mg-btn main flex-1" data-send-now>
                                    <span wire:loading.remove wire:target="send">Send to {{ $num($preview['reachable']) }}</span>
                                    <span wire:loading wire:target="send">Sending…</span>
                                </button>
                            @else
                                <button type="button" wire:click="review" wire:loading.attr="disabled" wire:target="review" class="mg-btn main w-full" data-send-review>Review &amp; Send</button>
                            @endif
                        </div>
                        @if ($confirming)
                            <p class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2">Ready — press Send to send it.</p>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- ════════════════════ SENT ════════════════════ --}}
        @if ($tab === 'sent' && $tracked)
            <div class="sa-card overflow-hidden">
                <div class="px-5 sm:px-6 pt-5 pb-3"><h3 class="sa-card-title">Sent to Many</h3><p class="sa-card-sub">Your notices and how far each got</p></div>
                @include('livewire.partials.messaging-campaigns', ['campaigns' => $campaigns, 'showSchool' => false])
            </div>
        @endif

        {{-- ════════════════════ USAGE ════════════════════ --}}
        @if ($tab === 'usage')
            <div class="sa-card p-5 sm:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="mg-ch-ic whatsapp"><x-icon name="chat-bubble-left-right" /></span>
                    <h3 class="sa-card-title">WhatsApp</h3>
                </div>
                @if ($wa = $this->whatsAppAnalytics())
                    @include('livewire.partials.whatsapp-analytics', ['wa' => $wa, 'waEditable' => false, 'waKey' => 'admin-messaging-' . auth()->user()->organization_id])
                @else
                    <p class="text-center py-10 text-sm text-gray-400">No WhatsApp messages yet.</p>
                @endif
            </div>

            @foreach (['sms' => $sms, 'email' => $email] as $uch => $u)
                @if ($u)
                    <div class="sa-card overflow-hidden">
                        <div class="px-5 sm:px-6 pt-5 pb-3 flex flex-wrap items-center gap-3">
                            <span class="mg-ch-ic {{ $uch }}"><x-icon name="{{ $icons[$uch] }}" /></span>
                            <h3 class="sa-card-title">{{ $C[$uch] }}</h3>
                            <span class="ml-auto text-xs text-gray-500">This month: {{ $num($u['month']['sent']) }} sent · {{ $num($u['month']['failed']) }} failed{{ $uch === 'sms' ? ' · ' . $num($u['month']['parts']) . ' parts' : '' }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="sk-table">
                                <thead><tr><th>When</th><th>To</th><th>{{ $uch === 'sms' ? 'Kind' : 'Subject' }}</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($u['messages'] as $row)
                                        <tr wire:key="u-{{ $uch }}-{{ $row['id'] }}">
                                            <td class="whitespace-nowrap text-gray-500 tabular-nums">{{ optional($row['at'])->format('d M Y, h:i A') }}</td>
                                            <td><span class="text-gray-900">{{ $row['name'] }}</span> <span class="text-xs text-gray-400">· {{ $row['to'] }}</span></td>
                                            <td class="text-gray-600 break-words">{{ $uch === 'sms' ? $row['kind'] : $row['subject'] }}</td>
                                            <td><span class="an-tag {{ $row['status'] === 'sent' ? 'green' : ($row['status'] === 'failed' ? 'rose' : '') }}">{{ ucfirst($row['status']) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-sm text-gray-400" style="padding: 32px 12px">No {{ $C[$uch] }} sent yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

        {{-- ════════════════════ PACKS & PAYMENTS ════════════════════ --}}
        @if ($tab === 'history' && $tracked)
            <dl class="sd-figs" style="--c: 4">
                @foreach ($C as $ch => $label)
                    <div><dt>{{ $label }} given</dt><dd>{{ $num($given[$ch] ?? 0) }}</dd></div>
                @endforeach
                <div><dt>To pay SuperLMS</dt><dd class="{{ $money['due'] > 0 ? 'down' : '' }}">{{ $inr($money['due']) }}</dd></div>
            </dl>

            <div class="sa-card overflow-hidden" data-history-packs>
                <div class="px-5 sm:px-6 pt-5 pb-3"><h3 class="sa-card-title">Packs</h3><p class="sa-card-sub">Messages you bought, and those SuperLMS gave you</p></div>
                <div class="overflow-x-auto">
                    <table class="sk-table">
                        <thead><tr><th>Date</th><th>Channel</th><th class="num">Messages</th><th class="num">Price</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($packs as $pk)
                                @php [$stLabel, $stTone] = $packState($state[$pk->id] ?? null, $pk); @endphp
                                <tr wire:key="hp-{{ $pk->id }}">
                                    <td class="whitespace-nowrap text-gray-500">{{ $pk->txn_date?->format('d M Y') }}<span class="block text-[11px] text-gray-400">{{ $pk->source === 'school' ? 'Bought by you' : ($pk->source === 'carried' ? 'From your wallet' : 'From SuperLMS') }}</span></td>
                                    <td><span class="an-tag {{ $pk->channel }}">{{ \App\Services\MessagePacks::label($pk->channel) }}</span></td>
                                    <td class="num font-semibold {{ $pk->type === 'take' ? 'text-rose-600' : '' }}">{{ $pk->type === 'take' ? '−' : '+' }}{{ $num($pk->messages) }}</td>
                                    <td class="num">{{ $pk->type === 'take' ? '—' : ((float) $pk->amount > 0 ? $inr($pk->amount) : 'Free') }}</td>
                                    <td>
                                        <span class="an-tag {{ $stTone }}">{{ $stLabel }}</span>
                                        @if ($pk->decision_note)<span class="block text-xs text-gray-400 mt-0.5">{{ $pk->decision_note }}</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-sm text-gray-400" style="padding: 40px 12px">No packs yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($packs->hasPages())<div class="mg-pages">{{ $packs->links('livewire::tailwind') }}</div>@endif
            </div>

            <div class="sa-card overflow-hidden" data-history-payments>
                <div class="px-5 sm:px-6 pt-5 pb-3"><h3 class="sa-card-title">Payments</h3><p class="sa-card-sub">What SuperLMS received from you for messages</p></div>
                <div class="overflow-x-auto">
                    <table class="sk-table">
                        <thead><tr><th>Paid on</th><th>For</th><th>Mode</th><th class="num">Amount</th></tr></thead>
                        <tbody>
                            @forelse ($payments as $p)
                                <tr wire:key="hpay-{{ $p->id }}">
                                    <td class="whitespace-nowrap text-gray-500">{{ $p->paid_on?->format('d M Y') }}</td>
                                    <td>@if ($p->pack && $p->pack->messages){{ $num($p->pack->messages) }} {{ \App\Services\MessagePacks::label($p->pack->channel) }}@else<span class="text-gray-400">What you owed</span>@endif</td>
                                    <td class="text-gray-600">{{ trim(($p->mode ?? '') . ($p->reference ? ' · Ref. ' . $p->reference : '')) ?: '—' }}</td>
                                    <td class="num font-semibold text-emerald-700">{{ $inr($p->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-sm text-gray-400" style="padding: 32px 12px">No payment yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
