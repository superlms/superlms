<div class="sa-skin sa-home min-h-screen">
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')
    @include('livewire.partials.messaging-styles')

    {{-- Messaging in the Home's analytics look (2026-11-21): the messages each
         school has on WhatsApp, SMS and email, the packs that gave them and
         the money for them — a chip a tab, Give Messages and Record Payment
         in the header. --}}
    @php
        $C        = \App\Services\MessagePacks::CHANNELS;
        $icons    = ['whatsapp' => 'chat-bubble-left-right', 'sms' => 'device-phone-mobile', 'email' => 'envelope'];
        $colors   = ['whatsapp' => '#0d9488', 'sms' => '#0ea5e9', 'email' => '#f59e0b'];
        $num      = fn ($v) => number_format((int) $v);
        $inr      = fn ($v) => '₹' . number_format((float) $v, 2);
        $inr0     = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
        $rate     = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
        $trend    = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));
        $initials = fn ($name) => mb_strtoupper(mb_substr(trim((string) $name), 0, 1) . (str_contains(trim((string) $name), ' ') ? mb_substr(trim(strrchr(trim((string) $name), ' ')), 0, 1) : ''));
        $leftAll  = array_sum(array_column($channels, 'left'));
        $chipIcons = [
            'overview' => 'chart-bar', 'schools' => 'building-office-2', 'requests' => 'inbox-arrow-down', 'packs' => 'cube',
            'payments' => 'banknotes', 'whatsapp' => 'chat-bubble-left-right', 'sms' => 'device-phone-mobile', 'email' => 'envelope',
            'sent' => 'paper-airplane', 'settings' => 'cog-6-tooth',
        ];
        $schoolName = $school !== '' ? ($schools->firstWhere('id', (int) $school)?->name ?? null) : null;
        $stateTag = function (?array $st, $pack) use ($inr) {
            if ($pack->type === 'take') return ['Taken back', ''];
            return match ($st['state'] ?? 'free') {
                'paid'  => ['Paid', 'green'],
                'part'  => ['Part paid · ' . $inr($st['paid']), 'amber'],
                'due'   => ['To pay', 'rose'],
                default => ['Free', 'teal'],
            };
        };
        $canSchoolPick = $schools->count() > 1;
    @endphp

    {{-- ══════════ HEADER ══════════ --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>Messaging</h1>
            <p class="hidden sm:block">WhatsApp, SMS and email messages for every school</p>
        </div>
        <div class="sk-right">
            @if ($tracked)
                <span class="hidden lg:inline-flex"><span class="sk-pill live" title="Messages left in every school">{{ $num($leftAll) }} left</span></span>
                <button type="button" wire:click="openPay" class="sk-pill" style="height: 36px; padding: 0 14px; font-size: 13.5px" data-record-payment>
                    <x-icon name="banknotes" class="w-4 h-4" /> Record Payment
                </button>
                <button type="button" wire:click="openGive" class="sk-cta" data-give-messages>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Give Messages
                </button>
            @endif
        </div>
    </div>

    {{-- ══════════ TABS ══════════ --}}
    <nav class="an-chips" aria-label="Messaging sections">
        @foreach (\App\Livewire\SuperAdmin\Messaging::TABS as $key => $label)
            @continue($key === 'settings' && !$canSettings)
            <button type="button" wire:click="setTab('{{ $key }}')" class="an-chip {{ $tab === $key ? 'on' : '' }}" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}" data-mg-chip="{{ $key }}">
                <x-icon name="{{ $chipIcons[$key] }}" /> {{ $label }}
                @if ($key === 'requests' && $totals['pending'])<span class="n">{{ $totals['pending'] }}</span>@endif
                @if ($key === 'payments' && $totals['owing'])<span class="n">{{ $totals['owing'] }}</span>@endif
            </button>
        @endforeach
    </nav>

    <div class="p-4 sm:p-6 pt-4 sm:pt-5 space-y-5">
        @unless ($tracked)
            <div class="sa-card px-6 py-14 text-center">
                <p class="text-sm font-semibold text-gray-800">Messaging is being set up</p>
                <p class="text-xs text-gray-400 mt-1">Its tables come with the next migration.</p>
            </div>
        @endunless

        {{-- ════════════════════ OVERVIEW ════════════════════ --}}
        @if ($tab === 'overview' && $tracked)
            @if ($totals['pending'])
                <button type="button" wire:click="setTab('requests')" class="w-full mg-warn text-left" data-pending-note>
                    <x-icon name="inbox-arrow-down" />
                    <span><b>{{ $totals['pending'] }} {{ $totals['pending'] === 1 ? 'school has' : 'schools have' }} bought messages</b> — approve {{ $totals['pending'] === 1 ? 'it' : 'them' }} on Requests and the messages go into their account.</span>
                </button>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" data-mg-kpis>
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Messages left', 'value' => $num($leftAll), 'icon' => 'inbox-stack', 'color' => 'teal',
                    'series' => $monthly['given'], 'trend' => null, 'flat' => false,
                    'sub' => collect($C)->map(fn ($l, $k) => $l . ' ' . $num($channels[$k]['left']))->implode(' · '),
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Sent this month', 'value' => $num($totals['month']), 'icon' => 'paper-airplane', 'color' => 'sky',
                    'series' => $monthly['total'], 'trend' => $trend($monthly['total']),
                    'sub' => array_sum($failed) ? $num(array_sum($failed)) . ' failed this month — they take no message' : 'None failed this month',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Received this month', 'value' => $inr0($receivedMonth), 'icon' => 'banknotes', 'color' => 'emerald',
                    'series' => $monthly['paid'], 'trend' => $trend($monthly['paid']),
                    'sub' => $inr0($totals['paid']) . ' received for messages in all',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'To collect', 'value' => $inr0($totals['due']), 'icon' => 'clock', 'color' => $totals['due'] > 0 ? 'rose' : 'amber',
                    'series' => [], 'trend' => null, 'flat' => false, 'click' => "setTab('payments')",
                    'sub' => $totals['owing'] ? $totals['owing'] . ' ' . ($totals['owing'] === 1 ? 'school owes' : 'schools owe') . ' for messages given on Pay later' : 'Every pack is paid for',
                ])
            </div>

            {{-- Each channel: given, sent, left --}}
            <div class="sa-card p-5 sm:p-6" data-mg-channels>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="sa-card-title">Messages by Channel</h3>
                        <p class="sa-card-sub">Given to the schools, sent since packs came, and what is left · Prepaid {{ $prepaid ? 'on' : 'off' }}</p>
                    </div>
                    <button type="button" wire:click="setTab('schools')" class="sk-pill">Every school <x-icon name="arrow-right" class="w-3.5 h-3.5" /></button>
                </div>
                <div class="mg-chs mt-5">
                    @foreach ($C as $ch => $label)
                        @php $c = $channels[$ch]; $pct = $c['given'] > 0 ? min(100, round($c['sent'] / $c['given'] * 100)) : ($c['sent'] ? 100 : 0); @endphp
                        <div class="mg-chc">
                            <div class="flex items-center gap-3">
                                <span class="mg-ch-ic {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ $label }}</p>
                                    <p class="text-xs text-gray-400">{{ $rate($rates[$ch]) }} a message{{ $ch === 'sms' ? ' part' : '' }}</p>
                                </div>
                                <span class="ml-auto an-tag {{ $ch }}">{{ $num($c['month']) }} this month</span>
                            </div>
                            <div class="mt-4 flex items-end justify-between gap-2">
                                <div>
                                    <p class="big">{{ $num($c['left']) }}</p>
                                    <p class="text-xs text-gray-400 mt-1">left of {{ $num($c['given']) }} given</p>
                                </div>
                                <p class="text-right text-xs text-gray-500">{{ $num($c['sent']) }} sent<br><span class="text-gray-400">{{ $pct }}% used</span></p>
                            </div>
                            <div class="mg-meter {{ $ch }} mt-3"><i class="{{ $c['over'] ? 'over' : '' }}" style="width: {{ $pct }}%"></i></div>
                            @if ($c['over'])
                                <p class="mt-2 text-xs text-rose-600">{{ $num($c['over']) }} sent beyond the packs (Prepaid is off)</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card p-5 sm:p-6" data-mg-monthly>
                    <h3 class="sa-card-title">Sent by Month</h3>
                    <p class="sa-card-sub">Every charged WhatsApp, SMS part and email of every school</p>
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
                <div class="sa-card p-5 sm:p-6" data-mg-split>
                    <h3 class="sa-card-title">This Month by Channel</h3>
                    <p class="sa-card-sub">{{ now()->format('F Y') }}</p>
                    <div class="mt-5">
                        @php $split = collect($C)->map(fn ($l, $k) => ['label' => $l, 'value' => $channels[$k]['month'], 'color' => $colors[$k]])->values()->all(); @endphp
                        @if (array_sum(array_column($split, 'value')))
                            @include('livewire.super-admin.partials.dash-donut', ['segments' => $split, 'center' => $num($totals['month']), 'centerLabel' => 'sent', 'format' => fn ($v) => number_format($v)])
                        @else
                            <p class="text-sm text-gray-400 py-10 text-center">No messages sent this month yet</p>
                        @endif
                    </div>
                    <div class="sd-foot">Failed this month (not counted): @foreach ($C as $ch => $label)<b>{{ $num($failed[$ch]) }}</b> {{ $label }}@if (!$loop->last) · @endif @endforeach</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card p-5 sm:p-6" data-mg-top>
                    <h3 class="sa-card-title">Top Schools This Month</h3>
                    <p class="sa-card-sub">Messages sent on every channel</p>
                    <div class="mt-5">
                        @include('livewire.super-admin.partials.an-bars', ['rows' => $topMonth, 'empty' => 'No school has sent a message this month', 'unit' => 'sent'])
                    </div>
                </div>
                <div class="sa-card p-5 sm:p-6" data-mg-low>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="sa-card-title">Running Low</h3>
                            <p class="sa-card-sub">Fewer than {{ \App\Services\MessagePacks::LOW }} left on a channel the school uses</p>
                        </div>
                        @if ($totals['low'])<span class="an-tag rose">{{ $totals['low'] }}</span>@endif
                    </div>
                    <div class="mt-3">
                        @forelse ($lowRows as $r)
                            <div class="an-row" wire:key="low-{{ $r['id'] }}">
                                <span class="an-av">@if ($r['logo'])<img src="{{ $r['logo'] }}" alt="">@else{{ $initials($r['name']) }}@endif</span>
                                <div class="min-w-0 flex-1">
                                    <button type="button" wire:click="openDetail({{ $r['id'] }})" class="text-sm font-semibold text-gray-900 truncate block max-w-full text-left hover:text-teal-700">{{ $r['name'] }}</button>
                                    <p class="text-xs text-gray-400 truncate">@foreach ($C as $ch => $label)@if ($r['given'][$ch] || $r['sent'][$ch]){{ $label }} <span class="{{ $r['left'][$ch] < \App\Services\MessagePacks::LOW ? 'text-rose-600 font-semibold' : '' }}">{{ $num($r['left'][$ch]) }}</span> &nbsp;@endif @endforeach</p>
                                </div>
                                <button type="button" wire:click="openGive({{ $r['id'] }})" class="mg-btn line sm">Give</button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 py-8 text-center">No school is running low</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card p-5 sm:p-6" data-mg-recent>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="sa-card-title">Latest Payments</h3>
                            <p class="sa-card-sub">Money received for messages — in the Ledger too</p>
                        </div>
                        <button type="button" wire:click="setTab('payments')" class="sk-pill">All <x-icon name="arrow-right" class="w-3.5 h-3.5" /></button>
                    </div>
                    <div class="mt-3">
                        @forelse ($recentPayments as $p)
                            <div class="an-row" wire:key="rp-{{ $p->id }}">
                                <span class="mg-ch-ic sm {{ $p->pack?->channel ?? 'whatsapp' }}"><x-icon name="currency-rupee" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $p->organization?->name ?? 'School' }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $p->paid_on?->format('d M Y') }}{{ $p->mode ? ' · ' . $p->mode : '' }}{{ $p->pack && $p->pack->messages ? ' · ' . $num($p->pack->messages) . ' ' . \App\Services\MessagePacks::label($p->pack->channel) : '' }}</p>
                                </div>
                                <span class="text-sm font-semibold text-emerald-700 tabular-nums">{{ $inr($p->amount) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 py-8 text-center">No payment yet</p>
                        @endforelse
                    </div>
                </div>
                <div class="sa-card p-5 sm:p-6" data-mg-requests-mini>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="sa-card-title">Bought by Schools</h3>
                            <p class="sa-card-sub">Waiting for your approval</p>
                        </div>
                        <button type="button" wire:click="setTab('requests')" class="sk-pill">Requests <x-icon name="arrow-right" class="w-3.5 h-3.5" /></button>
                    </div>
                    <div class="mt-3">
                        @forelse ($pendingList as $req)
                            <div class="an-row" wire:key="pl-{{ $req->id }}">
                                <span class="mg-ch-ic sm {{ $req->channel }}"><x-icon name="{{ $icons[$req->channel] ?? 'envelope' }}" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $req->organization?->name ?? 'School' }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $num($req->messages) }} {{ \App\Services\MessagePacks::label($req->channel) }} · {{ $inr($req->amount) }}{{ $req->reference ? ' · Ref. ' . $req->reference : '' }}</p>
                                </div>
                                <button type="button" wire:click="approveRequest({{ $req->id }})" wire:confirm="Add {{ $num($req->messages) }} {{ \App\Services\MessagePacks::label($req->channel) }} messages to {{ $req->organization?->name }} and keep {{ $inr($req->amount) }} as received?" class="mg-btn main sm">Approve</button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 py-8 text-center">Nothing to approve</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- ════════════════════ SCHOOLS ════════════════════ --}}
        @if ($tab === 'schools' && $tracked)
            <div class="sa-card overflow-hidden" data-mg-schools>
                <div class="sk-filter">
                    <div class="sk-search">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search a school…" aria-label="Search a school">
                    </div>
                    <select wire:model.live="show" class="sk-select" aria-label="Show">
                        <option value="">Every school</option>
                        <option value="low">Running low</option>
                        <option value="due">Owe money</option>
                        <option value="none">No messages yet</option>
                    </select>
                    <select wire:model.live="sort" class="sk-select" aria-label="Sort">
                        <option value="name">School (A–Z)</option>
                        <option value="left">Fewest left first</option>
                        <option value="used">Most sent this month</option>
                        <option value="due">Most to pay</option>
                    </select>
                    <span class="ml-auto text-xs text-gray-400">{{ $num($schoolRows->count()) }} {{ $schoolRows->count() === 1 ? 'school' : 'schools' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sk-table">
                        <thead>
                            <tr>
                                <th>School</th>
                                @foreach ($C as $ch => $label)<th class="num">{{ $label }} left</th>@endforeach
                                <th class="num">This month</th>
                                <th class="num">To pay</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schoolRows as $r)
                                <tr wire:key="sch-{{ $r['id'] }}">
                                    <td>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="an-av">@if ($r['logo'])<img src="{{ $r['logo'] }}" alt="">@else{{ $initials($r['name']) }}@endif</span>
                                            <div class="min-w-0">
                                                <button type="button" wire:click="openDetail({{ $r['id'] }})" class="font-semibold text-gray-900 hover:text-teal-700 text-left">{{ $r['name'] }}</button>
                                                <div class="flex flex-wrap gap-1 mt-0.5">
                                                    @if (\App\Livewire\SuperAdmin\Messaging::isLow($r))<span class="an-tag rose">Running low</span>@endif
                                                    @if ($r['pending'])<span class="an-tag amber">{{ $r['pending'] }} to approve</span>@endif
                                                    @if (!array_sum($r['given']) && !array_sum($r['sent']))<span class="an-tag">No messages yet</span>@endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @foreach ($C as $ch => $label)
                                        <td class="num whitespace-nowrap">
                                            <span class="font-semibold {{ $r['left'][$ch] < 0 ? 'text-rose-600' : (($r['given'][$ch] || $r['sent'][$ch]) && $r['left'][$ch] < \App\Services\MessagePacks::LOW ? 'text-amber-600' : 'text-gray-900') }}">{{ $num($r['left'][$ch]) }}</span>
                                            <span class="block text-[11px] text-gray-400">of {{ $num($r['given'][$ch]) }}</span>
                                        </td>
                                    @endforeach
                                    <td class="num">{{ $num(array_sum($r['month'])) }}</td>
                                    <td class="num whitespace-nowrap {{ $r['due'] > 0 ? 'text-rose-600 font-semibold' : 'text-gray-400' }}">{{ $r['due'] > 0 ? $inr($r['due']) : '—' }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                            <button type="button" wire:click="openGive({{ $r['id'] }})" class="mg-btn line sm" title="Give messages">
                                                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg> Give
                                            </button>
                                            @if ($r['due'] > 0)
                                                <button type="button" wire:click="openPay({{ $r['id'] }})" class="mg-btn line sm" title="Record a payment">₹ Pay</button>
                                            @endif
                                            <button type="button" wire:click="openDetail({{ $r['id'] }})" class="sk-go" title="Open"><x-icon name="arrow-right" /></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-sm text-gray-400" style="padding: 48px 12px">No school matches.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ════════════════════ REQUESTS ════════════════════ --}}
        @if ($tab === 'requests' && $tracked)
            <div class="sa-card p-5 sm:p-6" data-mg-to-approve>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="sa-card-title">To Approve</h3>
                        <p class="sa-card-sub">Messages a school bought (More → Messaging → Buy Messages). Approving puts them in its account and keeps the payment.</p>
                    </div>
                    @if ($pendingRequests->count())<span class="an-tag amber">{{ $pendingRequests->count() }}</span>@endif
                </div>
                <div class="mt-3">
                    @forelse ($pendingRequests as $req)
                        <div class="an-row flex-wrap" wire:key="req-{{ $req->id }}">
                            <span class="mg-ch-ic {{ $req->channel }}"><x-icon name="{{ $icons[$req->channel] ?? 'envelope' }}" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900">{{ $req->organization?->name ?? 'School' }} · {{ $num($req->messages) }} {{ \App\Services\MessagePacks::label($req->channel) }} · <span class="text-emerald-700">{{ $inr($req->amount) }}</span></p>
                                <p class="text-xs text-gray-500 break-words">
                                    Paid {{ $req->txn_date?->format('d M Y') }}
                                    @if ($req->mode) · {{ $req->mode }} @endif
                                    @if ($req->reference) · Ref. {{ $req->reference }} @endif
                                    @if ($req->creator) · by {{ $req->creator->name }} @endif
                                    · {{ $rate($req->rate) }} a message
                                </p>
                                @if ($req->note)<p class="text-xs text-gray-400 break-words">{{ $req->note }}</p>@endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="openReject({{ $req->id }})" class="mg-btn rose sm">Reject</button>
                                <button type="button" wire:click="approveRequest({{ $req->id }})" wire:confirm="Add {{ $num($req->messages) }} {{ \App\Services\MessagePacks::label($req->channel) }} messages to {{ $req->organization?->name }} and keep {{ $inr($req->amount) }} as received?" class="mg-btn main sm">
                                    <x-icon name="check" /> Approve
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-10 text-center">Nothing to approve.</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card overflow-hidden" data-mg-decided>
                <div class="px-5 sm:px-6 pt-5 pb-3"><h3 class="sa-card-title">Decided</h3><p class="sa-card-sub">The last 30</p></div>
                <div class="overflow-x-auto">
                    <table class="sk-table">
                        <thead><tr><th>School</th><th>Messages</th><th class="num">Amount</th><th>Paid by</th><th>Status</th><th>Decided</th></tr></thead>
                        <tbody>
                            @forelse ($decidedRequests as $req)
                                <tr wire:key="dec-{{ $req->id }}">
                                    <td class="font-medium text-gray-900">{{ $req->organization?->name ?? 'School' }}</td>
                                    <td><span class="an-tag {{ $req->channel }}">{{ \App\Services\MessagePacks::label($req->channel) }}</span> {{ $num($req->messages) }}</td>
                                    <td class="num">{{ $inr($req->amount) }}</td>
                                    <td class="text-gray-500">{{ trim(($req->mode ?? '') . ($req->reference ? ' · Ref. ' . $req->reference : '')) ?: '—' }}</td>
                                    <td>
                                        <span class="an-tag {{ $req->status === 'approved' ? 'green' : 'rose' }}">{{ ucfirst($req->status) }}</span>
                                        @if ($req->decision_note)<span class="block text-xs text-gray-400 mt-0.5">{{ $req->decision_note }}</span>@endif
                                    </td>
                                    <td class="text-gray-500 whitespace-nowrap">{{ $req->decided_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-sm text-gray-400" style="padding: 36px 12px">None yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ════════════════════ PACKS ════════════════════ --}}
        @if ($tab === 'packs' && $tracked)
            <div class="sa-card overflow-hidden" data-mg-packs>
                <div class="sk-filter">
                    @if ($canSchoolPick)
                        <select wire:model.live="school" class="sk-select" aria-label="School" style="max-width: 16rem">
                            <option value="">Every school</option>
                            @foreach ($schools as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    @endif
                    <select wire:model.live="packChannel" class="sk-select" aria-label="Channel">
                        <option value="">Every channel</option>
                        @foreach ($C as $ch => $label)<option value="{{ $ch }}">{{ $label }}</option>@endforeach
                    </select>
                    <select wire:model.live="packState" class="sk-select" aria-label="Payment">
                        <option value="">Any payment</option>
                        <option value="paid">Paid</option>
                        <option value="part">Part paid</option>
                        <option value="due">To pay</option>
                        <option value="free">Free</option>
                        <option value="take">Taken back</option>
                    </select>
                    <span class="ml-auto text-xs text-gray-400">{{ $num($packs->total()) }} {{ $packs->total() === 1 ? 'pack' : 'packs' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sk-table">
                        <thead><tr><th>Date</th><th>School</th><th>Channel</th><th class="num">Messages</th><th class="num">Price</th><th>Payment</th><th>By</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($packs as $pk)
                                @php [$stLabel, $stTone] = $stateTag($packPay[$pk->id] ?? null, $pk); @endphp
                                <tr wire:key="pk-{{ $pk->id }}">
                                    <td class="whitespace-nowrap text-gray-500">{{ $pk->txn_date?->format('d M Y') }}</td>
                                    <td>
                                        <button type="button" wire:click="openDetail({{ $pk->organization_id }})" class="font-medium text-gray-900 hover:text-teal-700 text-left">{{ $pk->organization?->name ?? 'School' }}</button>
                                        @if ($pk->note)<span class="block text-xs text-gray-400 max-w-xs truncate" title="{{ $pk->note }}">{{ $pk->note }}</span>@endif
                                    </td>
                                    <td><span class="an-tag {{ $pk->channel }}">{{ \App\Services\MessagePacks::label($pk->channel) }}</span></td>
                                    <td class="num font-semibold {{ $pk->type === 'take' ? 'text-rose-600' : 'text-gray-900' }}">{{ $pk->type === 'take' ? '−' : '+' }}{{ $num($pk->messages) }}</td>
                                    <td class="num">{{ $pk->type === 'take' ? '—' : ((float) $pk->amount > 0 ? $inr($pk->amount) : 'Free') }}@if ($pk->type !== 'take' && (float) $pk->amount > 0)<span class="block text-[11px] text-gray-400">{{ $rate($pk->rate) }} each</span>@endif</td>
                                    <td><span class="an-tag {{ $stTone }}">{{ $stLabel }}</span></td>
                                    <td class="text-gray-500 whitespace-nowrap">{{ $pk->source === 'school' ? 'School' : ($pk->source === 'carried' ? 'Wallet carried over' : ($pk->creator?->name ?? 'SuperLMS')) }}</td>
                                    <td class="text-right">
                                        @if (in_array($packPay[$pk->id]['state'] ?? '', ['due', 'part'], true))
                                            <button type="button" wire:click="openPay(null, {{ $pk->id }})" class="mg-btn line sm">₹ Pay</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-sm text-gray-400" style="padding: 48px 12px">No packs yet — Give Messages puts the first one in.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($packs->hasPages())<div class="mg-pages">{{ $packs->links('livewire::tailwind') }}</div>@endif
            </div>
        @endif

        {{-- ════════════════════ PAYMENTS ════════════════════ --}}
        @if ($tab === 'payments' && $tracked)
            <dl class="sd-figs" style="--c: 4" data-mg-pay-figs>
                <div><dt>Received this month</dt><dd class="up">{{ $inr($receivedMonth) }}</dd></div>
                <div><dt>Received in all</dt><dd>{{ $inr($totals['paid']) }}</dd></div>
                <div><dt>To collect</dt><dd class="{{ $totals['due'] > 0 ? 'down' : '' }}">{{ $inr($totals['due']) }}</dd></div>
                <div><dt>Schools that owe</dt><dd>{{ $num($totals['owing']) }}</dd></div>
            </dl>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card overflow-hidden" data-mg-owing>
                    <div class="px-5 sm:px-6 pt-5 pb-3"><h3 class="sa-card-title">Who Owes</h3><p class="sa-card-sub">Packs given on Pay later, less what came in</p></div>
                    <div class="overflow-x-auto">
                        <table class="sk-table">
                            <thead><tr><th>School</th><th class="num">Packs</th><th class="num">Paid</th><th class="num">To pay</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($owing as $r)
                                    <tr wire:key="ow-{{ $r['id'] }}">
                                        <td class="font-medium text-gray-900">{{ $r['name'] }}</td>
                                        <td class="num">{{ $inr($r['charged']) }}</td>
                                        <td class="num text-emerald-700">{{ $inr($r['paid']) }}</td>
                                        <td class="num font-semibold text-rose-600">{{ $inr($r['due']) }}</td>
                                        <td class="text-right"><button type="button" wire:click="openPay({{ $r['id'] }})" class="mg-btn main sm">Record</button></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-sm text-gray-400" style="padding: 36px 12px">Nobody owes anything.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="sa-card p-5 sm:p-6" data-mg-paid-monthly>
                    <h3 class="sa-card-title">Received by Month</h3>
                    <p class="sa-card-sub">For messages, the last six months</p>
                    @include('livewire.super-admin.partials.month-bars', ['values' => $monthly['paid'], 'labels' => $monthly['labels'], 'format' => fn ($v) => '₹' . number_format($v), 'empty' => 'No payment yet'])
                    <div class="sd-foot">This month <b>{{ $inr($receivedMonth) }}</b> · in all <b>{{ $inr($totals['paid']) }}</b> — each payment is a credit in the Ledger (Messaging Wallet).</div>
                </div>
            </div>

            <div class="sa-card overflow-hidden" data-mg-payments>
                <div class="sk-filter">
                    <h3 class="sa-card-title mr-auto">Payments</h3>
                    @if ($canSchoolPick)
                        <select wire:model.live="school" class="sk-select" aria-label="School" style="max-width: 16rem">
                            <option value="">Every school</option>
                            @foreach ($schools as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="sk-table">
                        <thead><tr><th>Paid on</th><th>School</th><th>For</th><th>Mode</th><th>Reference</th><th class="num">Amount</th><th>By</th></tr></thead>
                        <tbody>
                            @forelse ($payments as $p)
                                <tr wire:key="pay-{{ $p->id }}">
                                    <td class="whitespace-nowrap text-gray-500">{{ $p->paid_on?->format('d M Y') }}</td>
                                    <td class="font-medium text-gray-900">{{ $p->organization?->name ?? 'School' }}</td>
                                    <td>@if ($p->pack && $p->pack->messages)<span class="an-tag {{ $p->pack->channel }}">{{ \App\Services\MessagePacks::label($p->pack->channel) }}</span> {{ $num($p->pack->messages) }}@else<span class="text-gray-400">What it owes</span>@endif
                                        @if ($p->note)<span class="block text-xs text-gray-400">{{ $p->note }}</span>@endif</td>
                                    <td class="text-gray-600">{{ $p->mode ?: '—' }}</td>
                                    <td class="text-gray-500">{{ $p->reference ?: '—' }}</td>
                                    <td class="num font-semibold text-emerald-700">{{ $inr($p->amount) }}</td>
                                    <td class="text-gray-500 whitespace-nowrap">{{ $p->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-sm text-gray-400" style="padding: 48px 12px">No payment yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($payments->hasPages())<div class="mg-pages">{{ $payments->links('livewire::tailwind') }}</div>@endif
            </div>
        @endif

        {{-- ════════════════════ WHATSAPP / SMS / EMAIL — every school ════════════════════ --}}
        @if (in_array($tab, ['whatsapp', 'sms', 'email'], true) && $tracked && $school === '')
            @php $ch = $tab; $c = $channels[$ch]; @endphp
            <div class="sa-card p-5 sm:p-6" data-mg-channel="{{ $ch }}">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="mg-ch-ic {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                    <div class="min-w-0">
                        <h3 class="sa-card-title">{{ $C[$ch] }} — Every School</h3>
                        <p class="sa-card-sub">{{ $rate($rates[$ch]) }} a message{{ $ch === 'sms' ? ' part' : '' }} · {{ $ch === 'whatsapp' ? 'absent alert, fee received, Send Credentials and notices' : ($ch === 'sms' ? 'welcome, Send Credentials and notices' : 'notices to students and teachers') }}</p>
                    </div>
                    <button type="button" wire:click="openGive(null, '{{ $ch }}')" class="mg-btn main sm ml-auto">
                        <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg> Give {{ $C[$ch] }}
                    </button>
                </div>
                <dl class="sd-figs mt-5" style="--c: 5">
                    <div><dt>Given</dt><dd>{{ $num($c['given']) }}</dd></div>
                    <div><dt>Sent since packs</dt><dd>{{ $num($c['sent']) }}</dd></div>
                    <div><dt>Left</dt><dd class="{{ $c['left'] > 0 ? 'up' : '' }}">{{ $num($c['left']) }}</dd></div>
                    <div><dt>This month</dt><dd>{{ $num($c['month']) }}</dd></div>
                    <div><dt>Schools with it on</dt><dd>{{ $num($channelRows->where('on', true)->count()) }}</dd></div>
                </dl>
                @if ($ch === 'sms' && !$smsConfigured)
                    <div class="mg-warn mt-4"><x-icon name="exclamation-triangle" /><span>Bhash SMS is not set up on the server yet (BHASH_SMS_USER, BHASH_SMS_PASS, BHASH_SMS_SENDER) — no SMS goes until it is.</span></div>
                @endif
                @if ($channelMonthly)
                    <div class="mt-2">
                        @include('livewire.super-admin.partials.month-bars', ['values' => $channelMonthly[$ch], 'labels' => $channelMonthly['labels'], 'height' => 110, 'empty' => 'No ' . $C[$ch] . ' sent in the last six months'])
                    </div>
                @endif
            </div>

            <div class="sa-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="sk-table">
                        <thead><tr><th>School</th><th>Switched</th><th class="num">Given</th><th class="num">Sent</th><th class="num">Left</th><th class="num">This month</th><th class="num">Failed</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($channelRows as $r)
                                <tr wire:key="chr-{{ $ch }}-{{ $r['id'] }}">
                                    <td>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="an-av">@if ($r['logo'])<img src="{{ $r['logo'] }}" alt="">@else{{ $initials($r['name']) }}@endif</span>
                                            <button type="button" wire:click="openSchool({{ $r['id'] }}, '{{ $ch }}')" class="font-semibold text-gray-900 hover:text-teal-700 text-left">{{ $r['name'] }}</button>
                                        </div>
                                    </td>
                                    <td><span class="an-tag {{ $r['on'] ? 'green' : '' }}">{{ $r['on'] ? 'On' : 'Off' }}</span></td>
                                    <td class="num">{{ $num($r['given'][$ch]) }}</td>
                                    <td class="num">{{ $num($r['sent'][$ch]) }}</td>
                                    <td class="num font-semibold {{ $r['left'][$ch] < 0 ? 'text-rose-600' : (($r['given'][$ch] || $r['sent'][$ch]) && $r['left'][$ch] < \App\Services\MessagePacks::LOW ? 'text-amber-600' : 'text-gray-900') }}">{{ $num($r['left'][$ch]) }}</td>
                                    <td class="num">{{ $num($r['month'][$ch]) }}</td>
                                    <td class="num {{ $r['failed'] ? 'text-rose-600' : 'text-gray-400' }}">{{ $num($r['failed']) }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                            <button type="button" wire:click="openGive({{ $r['id'] }}, '{{ $ch }}')" class="mg-btn line sm">Give</button>
                                            <button type="button" wire:click="openSchool({{ $r['id'] }}, '{{ $ch }}')" class="sk-go" title="Switches and analytics"><x-icon name="arrow-right" /></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-sm text-gray-400" style="padding: 48px 12px">No schools yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ════════════════════ WHATSAPP / SMS / EMAIL — one school ════════════════════ --}}
        @if (in_array($tab, ['whatsapp', 'sms', 'email'], true) && $schoolName)
            @php $ch = $tab; $sr = $schoolRow ?? null; @endphp
            <div class="sa-card p-5 sm:p-6" data-mg-school-channel="{{ $ch }}">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($canSchoolPick)
                        <button type="button" wire:click="allSchools" class="sk-back" title="Every school"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg></button>
                    @endif
                    <span class="mg-ch-ic {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                    <div class="min-w-0">
                        <h3 class="sa-card-title truncate">{{ $schoolName }}</h3>
                        <p class="sa-card-sub">{{ $C[$ch] }} · {{ $rate($rates[$ch]) }} a message{{ $ch === 'sms' ? ' part' : '' }}</p>
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        @if ($canSchoolPick)
                            <select wire:model.live="school" class="sk-select" aria-label="School" style="height: 32px; max-width: 14rem">
                                @foreach ($schools as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        @endif
                        <button type="button" wire:click="openGive({{ (int) $school }}, '{{ $ch }}')" class="mg-btn main sm">Give {{ $C[$ch] }}</button>
                    </div>
                </div>
                @if ($sr)
                    <dl class="sd-figs mt-5" style="--c: 4">
                        <div><dt>Given</dt><dd>{{ $num($sr['given'][$ch]) }}</dd></div>
                        <div><dt>Sent since packs</dt><dd>{{ $num($sr['sent'][$ch]) }}</dd></div>
                        <div><dt>Left</dt><dd class="{{ $sr['left'][$ch] < 0 ? 'down' : 'up' }}">{{ $num($sr['left'][$ch]) }}</dd></div>
                        <div><dt>This month</dt><dd>{{ $num($sr['month'][$ch]) }}</dd></div>
                    </dl>
                @endif
            </div>

            @if ($ch === 'whatsapp')
                <div class="sa-card p-5 sm:p-6">
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

            @if ($ch === 'sms')
                @if ($sms = $this->smsSummary())
                    <div class="sa-card p-5 sm:p-6">
                        <h3 class="sa-card-title">Switches</h3>
                        <p class="sa-card-sub">Each SMS takes one SMS message a part from the school's packs</p>
                        <div class="mt-4">
                            <div class="mg-switch">
                                <button type="button" role="switch" aria-checked="{{ $sms['enabled'] ? 'true' : 'false' }}" wire:click="toggleSms('welcome')" wire:loading.attr="disabled" wire:target="toggleSms" class="mg-sw {{ $sms['enabled'] ? 'on' : '' }}" title="{{ $sms['enabled'] ? 'Turn off' : 'Turn on' }}"></button>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">Welcome by SMS <span class="an-tag {{ $sms['enabled'] ? 'green' : '' }} ml-1">{{ $sms['enabled'] ? 'On' : 'Off' }}</span></p>
                                    <p class="text-xs text-gray-500 leading-relaxed mt-1">When on, a new student gets an SMS with the school, class, section, admission number, password and the app link, and a new teacher one with their username, password and the app link — and <strong class="text-gray-700">no welcome email and no WhatsApp</strong>. A changed mobile number and Send Credentials go by SMS too. With Prepaid on and no SMS left, the email and WhatsApp go instead.</p>
                                </div>
                            </div>
                            <div class="mg-switch">
                                <button type="button" role="switch" aria-checked="{{ $sms['notice'] ? 'true' : 'false' }}" wire:click="toggleSms('notice')" wire:loading.attr="disabled" wire:target="toggleSms" class="mg-sw {{ $sms['notice'] ? 'on' : '' }}" title="{{ $sms['notice'] ? 'Turn off' : 'Turn on' }}"></button>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">SMS notices <span class="an-tag {{ $sms['notice'] ? 'green' : '' }} ml-1">{{ $sms['notice'] ? 'On' : 'Off' }}</span></p>
                                    <p class="text-xs text-gray-500 leading-relaxed mt-1">When on, the school can send a short notice (up to {{ \App\Services\SchoolSms::NOTICE_MAX }} letters) to its students and teachers by SMS from More → Messaging → Send.</p>
                                    @unless ($sms['noticeReady'])
                                        <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">The DLT notice template is not set up (BHASH_SMS_TEMPLATE_NOTICE). Register on DLT: <code>Notice from {#var#}: {#var#} {#var#} {#var#} {#var#} {#var#} - SuperLMS</code></p>
                                    @endunless
                                </div>
                            </div>
                        </div>
                        @unless ($sms['configured'])
                            <div class="mg-warn mt-3"><x-icon name="exclamation-triangle" /><span>Bhash SMS is not set up on the server yet (BHASH_SMS_USER, BHASH_SMS_PASS, BHASH_SMS_SENDER), so this school still gets the email and WhatsApp.</span></div>
                        @endunless
                    </div>

                    <dl class="sd-figs" style="--c: 4">
                        <div><dt>Sent this month</dt><dd>{{ $num($sms['month']['sent']) }}</dd></div>
                        <div><dt>Failed this month</dt><dd class="{{ $sms['month']['failed'] ? 'down' : '' }}">{{ $num($sms['month']['failed']) }}</dd></div>
                        <div><dt>SMS parts this month</dt><dd>{{ $num($sms['month']['parts']) }}</dd></div>
                        <div><dt>Sent in all</dt><dd>{{ $num($sms['total']) }}</dd></div>
                    </dl>

                    <div class="sa-card overflow-hidden">
                        <div class="px-5 sm:px-6 pt-5 pb-3"><h3 class="sa-card-title">Latest SMS</h3></div>
                        <div class="overflow-x-auto">
                            <table class="sk-table">
                                <thead><tr><th>When</th><th>To</th><th>Kind</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($sms['messages'] as $smsRow)
                                        <tr wire:key="sms-row-{{ $smsRow['id'] }}">
                                            <td class="whitespace-nowrap text-gray-500 tabular-nums">{{ optional($smsRow['at'])->format('d M Y, h:i A') }}</td>
                                            <td><span class="text-gray-900">{{ $smsRow['name'] }}</span><span class="text-xs text-gray-400"> · {{ $smsRow['who'] }} · {{ $smsRow['to'] }}</span></td>
                                            <td class="text-gray-600">{{ $smsRow['kind'] }}</td>
                                            <td>
                                                <span class="an-tag {{ $smsRow['status'] === 'sent' ? 'green' : ($smsRow['status'] === 'failed' ? 'rose' : '') }}">{{ ucfirst($smsRow['status']) }}</span>
                                                @if ($smsRow['status'] === 'failed' && $smsRow['error'])<span class="block text-xs text-gray-400 break-words max-w-xs mt-0.5">{{ $smsRow['error'] }}</span>@endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-sm text-gray-400" style="padding: 36px 12px">No SMS sent for this school yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="sa-card px-6 py-12 text-center text-sm text-gray-400">SMS is not set up yet.</div>
                @endif
            @endif

            @if ($ch === 'email')
                @if ($em = $this->emailSummary())
                    <div class="sa-card p-5 sm:p-6">
                        <div class="mg-switch">
                            <button type="button" role="switch" aria-checked="{{ $em['enabled'] ? 'true' : 'false' }}" wire:click="toggleEmail" wire:loading.attr="disabled" wire:target="toggleEmail" class="mg-sw {{ $em['enabled'] ? 'on' : '' }}" title="{{ $em['enabled'] ? 'Turn off' : 'Turn on' }}"></button>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">Email messaging <span class="an-tag {{ $em['enabled'] ? 'green' : '' }} ml-1">{{ $em['enabled'] ? 'On' : 'Off' }}</span></p>
                                <p class="text-xs text-gray-500 leading-relaxed mt-1">When on, the school can email its students and teachers from More → Messaging → Send; each email takes one email message from its packs (a failed one takes none). The email goes from SuperLMS's address with the school's name as the sender.</p>
                                @unless ($em['configured'])
                                    <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">ZeptoMail is not set up on the server (ZEPTOMAIL_API_TOKEN), so emails will fail.</p>
                                @endunless
                            </div>
                        </div>
                    </div>

                    <dl class="sd-figs" style="--c: 4">
                        <div><dt>Sent this month</dt><dd>{{ $num($em['month']['sent']) }}</dd></div>
                        <div><dt>Failed this month</dt><dd class="{{ $em['month']['failed'] ? 'down' : '' }}">{{ $num($em['month']['failed']) }}</dd></div>
                        <div><dt>Worth this month</dt><dd>{{ $inr($em['month']['amount']) }}</dd></div>
                        <div><dt>Sent in all</dt><dd>{{ $num($em['total']) }}</dd></div>
                    </dl>

                    <div class="sa-card overflow-hidden">
                        <div class="px-5 sm:px-6 pt-5 pb-3"><h3 class="sa-card-title">Latest Emails</h3></div>
                        <div class="overflow-x-auto">
                            <table class="sk-table">
                                <thead><tr><th>When</th><th>To</th><th>Subject</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($em['messages'] as $emRow)
                                        <tr wire:key="em-row-{{ $emRow['id'] }}">
                                            <td class="whitespace-nowrap text-gray-500 tabular-nums">{{ optional($emRow['at'])->format('d M Y, h:i A') }}</td>
                                            <td><span class="text-gray-900">{{ $emRow['name'] }}</span> <span class="text-xs text-gray-400">· {{ $emRow['to'] }}</span></td>
                                            <td class="text-gray-600 break-words">{{ $emRow['subject'] }}</td>
                                            <td>
                                                <span class="an-tag {{ $emRow['status'] === 'sent' ? 'green' : ($emRow['status'] === 'failed' ? 'rose' : '') }}">{{ ucfirst($emRow['status']) }}</span>
                                                @if ($emRow['status'] === 'failed' && $emRow['error'])<span class="block text-xs text-gray-400 break-words max-w-xs mt-0.5">{{ $emRow['error'] }}</span>@endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-sm text-gray-400" style="padding: 36px 12px">No email sent for this school yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="sa-card px-6 py-12 text-center text-sm text-gray-400">Email messaging is not set up yet.</div>
                @endif
            @endif
        @endif

        {{-- ════════════════════ SENT ════════════════════ --}}
        @if ($tab === 'sent' && $tracked)
            <div class="sa-card overflow-hidden">
                <div class="sk-filter">
                    <div class="mr-auto">
                        <h3 class="sa-card-title">Sent to Many</h3>
                        <p class="sa-card-sub">Notices schools sent from More → Messaging → Send, and how far each got</p>
                    </div>
                    @if ($canSchoolPick)
                        <select wire:model.live="school" class="sk-select" aria-label="School" style="max-width: 16rem">
                            <option value="">Every school</option>
                            @foreach ($schools as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    @endif
                </div>
                @include('livewire.partials.messaging-campaigns', ['campaigns' => $campaigns, 'showSchool' => $school === ''])
            </div>
        @endif

        {{-- ════════════════════ SETTINGS ════════════════════ --}}
        @if ($tab === 'settings' && $tracked && $canSettings)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                <div class="sa-card p-5 sm:p-6" data-mg-rates>
                    <h3 class="sa-card-title">Rates</h3>
                    <p class="sa-card-sub">What one message costs a school — the price Give Messages and Buy Messages start from</p>
                    <div class="mt-5 space-y-4">
                        @foreach (['whatsapp' => 'setWhatsappRate', 'sms' => 'setSmsRate', 'email' => 'setEmailRate'] as $ch => $field)
                            <div class="flex items-center gap-3">
                                <span class="mg-ch-ic {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                                <label class="flex-1 text-sm font-semibold text-gray-900" for="rate-{{ $ch }}">{{ $C[$ch] }} <span class="block text-xs font-normal text-gray-400">₹ a {{ $ch === 'sms' ? 'message part (160 letters)' : 'message' }}</span></label>
                                <div class="w-32">
                                    <input id="rate-{{ $ch }}" wire:model="{{ $field }}" type="number" step="0.01" min="0" class="mg-in text-right @error($field) err @enderror">
                                </div>
                            </div>
                            @error($field)<p class="mg-err text-right">{{ $message }}</p>@enderror
                        @endforeach
                    </div>
                    <div class="mg-switch mt-5" style="border-top: 1px solid #f0f3f2; padding-top: 16px">
                        <button type="button" role="switch" aria-checked="{{ $setPrepaid ? 'true' : 'false' }}" wire:click="$toggle('setPrepaid')" class="mg-sw {{ $setPrepaid ? 'on' : '' }}"></button>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Prepaid — send only while messages are left</p>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">On: a charged WhatsApp, SMS or email is not sent when the school has none left on that channel (it sees so on its Messaging page; a welcome SMS then goes by email and WhatsApp). Off: messages always go, and a school can go below zero, to be given a pack later.</p>
                        </div>
                    </div>
                </div>

                <div class="sa-card p-5 sm:p-6" data-mg-where-pay>
                    <h3 class="sa-card-title">Where Schools Pay</h3>
                    <p class="sa-card-sub">Shown on Buy Messages — with a UPI id the school gets a QR for the exact amount</p>
                    <div class="mt-5 grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="mg-label" for="upi-id">UPI id</label>
                            <input id="upi-id" wire:model.live.debounce.500ms="setUpiId" type="text" placeholder="superlms@okhdfcbank" class="mg-in @error('setUpiId') err @enderror">
                            @error('setUpiId')<p class="mg-err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mg-label" for="upi-name">Name on UPI</label>
                            <input id="upi-name" wire:model="setUpiName" type="text" placeholder="SuperLMS" class="mg-in">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mg-label" for="pay-details">Bank and other details</label>
                        <textarea id="pay-details" wire:model="setPaymentDetails" rows="4" placeholder="A/c 1234567890 · IFSC ABCD0001234 · SuperLMS" class="mg-in @error('setPaymentDetails') err @enderror"></textarea>
                        @error('setPaymentDetails')<p class="mg-err">{{ $message }}</p>@enderror
                    </div>
                    @if (trim($setUpiId) !== '' && preg_match('/^[A-Za-z0-9._\-]{2,}@[A-Za-z0-9.\-]{2,}$/', trim($setUpiId)))
                        <div class="mt-4 flex items-center gap-4">
                            <div class="mg-qr">{!! \App\Support\UpiQr::svg(\App\Support\UpiQr::link(trim($setUpiId), trim($setUpiName) ?: 'SuperLMS', 660, 'SuperLMS messages'), 180) !!}</div>
                            <p class="text-xs text-gray-500 leading-relaxed">How a school sees it, for ₹660 (1,000 WhatsApp messages). Scan it with a UPI app to check it opens <b class="text-gray-800">{{ trim($setUpiName) ?: 'SuperLMS' }}</b>.</p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" wire:click="saveSettings" wire:loading.attr="disabled" wire:target="saveSettings" class="mg-btn main">
                    <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                    <span wire:loading wire:target="saveSettings">Saving…</span>
                </button>
            </div>
        @endif
    </div>

    {{-- ══════════ GIVE MESSAGES / TAKE BACK ══════════ --}}
    @if ($showGive)
        @php
            $take   = $gType === 'take';
            $gTotal = $this->giveTotal();
            $gName  = $gOrgId !== '' ? ($schools->firstWhere('id', (int) $gOrgId)?->name ?? null) : null;
            $gLeft  = $giveRow['left'] ?? null;
        @endphp
        <div class="mg-sheet-wrap" data-give-sheet>
            <div class="mg-sheet-back" wire:click="closeGive"></div>
            <div class="mg-sheet">
                <div class="mg-sheet-h">
                    <div class="min-w-0 flex-1">
                        <h2>{{ $take ? 'Take Back Messages' : 'Give Messages' }}</h2>
                        <p>{{ $take ? 'Messages taken out of a school\'s account' : 'WhatsApp, SMS or email messages put in a school\'s account' }}</p>
                    </div>
                    <div class="mg-seg">
                        <button type="button" wire:click="$set('gType', 'give')" class="{{ !$take ? 'on' : '' }}">Give</button>
                        <button type="button" wire:click="$set('gType', 'take')" class="{{ $take ? 'on' : '' }}">Take back</button>
                    </div>
                    <button type="button" wire:click="closeGive" class="sk-back" aria-label="Close"><x-icon name="x-mark" /></button>
                </div>
                <div class="mg-sheet-b space-y-3">
                    <div class="mg-box">
                        <p class="mg-step"><b>1</b> School</p>
                        <select wire:model.live="gOrgId" class="mg-in @error('gOrgId') err @enderror" aria-label="School" @disabled(!$canSchoolPick && $gOrgId !== '')>
                            <option value="">Pick a school…</option>
                            @foreach ($schools as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        @error('gOrgId')<p class="mg-err">{{ $message }}</p>@enderror
                        @if ($gLeft)
                            <dl class="sd-figs mt-3" style="--c: 3">
                                @foreach ($C as $ch => $label)
                                    <div><dt>{{ $label }} left</dt><dd class="{{ $gLeft[$ch] < 0 ? 'down' : '' }}">{{ $num($gLeft[$ch]) }}</dd></div>
                                @endforeach
                            </dl>
                            @if (($giveRow['due'] ?? 0) > 0)
                                <p class="mg-hint">{{ $gName }} owes {{ $inr($giveRow['due']) }} for packs already given.</p>
                            @endif
                        @endif
                    </div>

                    <div class="mg-box">
                        <p class="mg-step"><b>2</b> Channel</p>
                        <div class="mg-opts">
                            @foreach ($C as $ch => $label)
                                <button type="button" wire:click="$set('gChannel', '{{ $ch }}')" class="mg-opt {{ $gChannel === $ch ? 'on' : '' }}" data-opt-channel="{{ $ch }}">
                                    <span class="tick"></span>
                                    <span class="mg-ch-ic sm {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                                    <b>{{ $label }}</b>
                                    <small>{{ $rate($rates[$ch]) }} a message{{ $ch === 'sms' ? ' part' : '' }}@if ($gLeft)<br>{{ $num($gLeft[$ch]) }} left @endif</small>
                                </button>
                            @endforeach
                        </div>
                        @error('gChannel')<p class="mg-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="mg-box">
                        <p class="mg-step"><b>3</b> {{ $take ? 'Messages to take back' : 'How many messages' }}</p>
                        @unless ($take)
                            <div class="mg-sizes mb-3">
                                @foreach (\App\Services\MessagePacks::SIZES as $size)
                                    <button type="button" wire:click="pickSize({{ $size }})" class="mg-size {{ (int) $gMessages === $size ? 'on' : '' }}">{{ $num($size) }}</button>
                                @endforeach
                            </div>
                        @endunless
                        <input wire:model.live.debounce.300ms="gMessages" type="number" min="1" step="1" placeholder="{{ $take ? 'e.g. 200' : 'Or type any number' }}" class="mg-in @error('gMessages') err @enderror" aria-label="Messages">
                        @error('gMessages')<p class="mg-err">{{ $message }}</p>@enderror
                        @if ($take)
                            <p class="mg-hint">Taken back messages leave the school's account; no money changes hands — record a refund in the Ledger if you pay one.</p>
                        @endif
                    </div>

                    @unless ($take)
                        <div class="mg-box">
                            <p class="mg-step"><b>4</b> Payment</p>
                            <div class="mg-opts">
                                @foreach (['paid' => ['Paid now', 'The school has paid — keep the payment'], 'due' => ['Pay later', 'The school owes it until you record a payment'], 'free' => ['Free', 'Costs the school nothing']] as $pv => [$pl, $ps])
                                    <button type="button" wire:click="$set('gPayment', '{{ $pv }}')" class="mg-opt {{ $gPayment === $pv ? 'on' : '' }}" data-opt-payment="{{ $pv }}">
                                        <span class="tick"></span>
                                        <b>{{ $pl }}</b>
                                        <small>{{ $ps }}</small>
                                    </button>
                                @endforeach
                            </div>

                            @if ($gPayment !== 'free')
                                <div class="grid grid-cols-2 gap-3 mt-4">
                                    <div>
                                        <label class="mg-label" for="g-rate">Rate a message (₹)</label>
                                        <input id="g-rate" wire:model.live.debounce.300ms="gRate" type="number" step="0.01" min="0" class="mg-in @error('gRate') err @enderror">
                                        @error('gRate')<p class="mg-err">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mg-label" for="g-date">{{ $gPayment === 'paid' ? 'Paid on' : 'Date' }}</label>
                                        <input id="g-date" wire:model="gDate" type="date" class="mg-in @error('gDate') err @enderror">
                                        @error('gDate')<p class="mg-err">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                @if ($gPayment === 'paid')
                                    <div class="grid grid-cols-2 gap-3 mt-3">
                                        <div>
                                            <label class="mg-label" for="g-mode">Paid by</label>
                                            <select id="g-mode" wire:model="gMode" class="mg-in">
                                                @foreach (\App\Services\MessagePacks::MODES as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mg-label" for="g-ref">Reference / UTR</label>
                                            <input id="g-ref" wire:model="gReference" type="text" placeholder="Transaction id" class="mg-in @error('gReference') err @enderror">
                                            @error('gReference')<p class="mg-err">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                @endif
                                <div class="mg-total mt-4">
                                    <div>
                                        <p class="text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ $gPayment === 'paid' ? 'Received' : 'To be paid' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $num((int) $gMessages) }} × {{ $rate((float) $gRate) }}</p>
                                    </div>
                                    <p class="sd-big">{{ $inr($gTotal) }}</p>
                                </div>
                            @endif
                        </div>
                    @endunless

                    <div class="mg-box">
                        <p class="mg-step"><b>{{ $take ? 4 : 5 }}</b> Note</p>
                        <textarea wire:model="gNote" rows="2" placeholder="{{ $take ? 'Why are they taken back?' : 'What is it for? (optional)' }}" class="mg-in @error('gNote') err @enderror"></textarea>
                        @error('gNote')<p class="mg-err">{{ $message }}</p>@enderror
                        @if ($take)
                            <input type="hidden" wire:model="gDate">
                        @endif
                    </div>

                    @if ($gName && (int) $gMessages > 0)
                        <div class="mg-say" data-give-summary>
                            <x-icon name="check" />
                            <span>
                                @if ($take)
                                    <b>{{ $num((int) $gMessages) }} {{ $C[$gChannel] }} messages</b> will be taken back from <b>{{ $gName }}</b>@if ($gLeft) — {{ $num(max(0, $gLeft[$gChannel] - (int) $gMessages)) }} left after.@else.@endif
                                @else
                                    <b>{{ $gName }}</b> gets <b>{{ $num((int) $gMessages) }} {{ $C[$gChannel] }} messages</b>@if ($gLeft) — {{ $num($gLeft[$gChannel] + (int) $gMessages) }} left after ·@else ·@endif
                                    {{ $gPayment === 'free' ? 'free' : ($gPayment === 'paid' ? $inr($gTotal) . ' received by ' . $gMode : $inr($gTotal) . ' to be paid') }}.
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
                <div class="mg-sheet-f">
                    <button type="button" wire:click="closeGive" class="mg-btn ghost">Cancel</button>
                    <button type="button" wire:click="saveGive" wire:loading.attr="disabled" wire:target="saveGive" class="mg-btn {{ $take ? 'rose' : 'main' }}" data-give-save>
                        <span wire:loading.remove wire:target="saveGive">{{ $take ? 'Take Back' : 'Give Messages' }}</span>
                        <span wire:loading wire:target="saveGive">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ RECORD PAYMENT ══════════ --}}
    @if ($showPay)
        @php $pName = $pOrgId !== '' ? ($schools->firstWhere('id', (int) $pOrgId)?->name ?? null) : null; @endphp
        <div class="mg-sheet-wrap" data-pay-sheet>
            <div class="mg-sheet-back" wire:click="closePay"></div>
            <div class="mg-sheet">
                <div class="mg-sheet-h">
                    <div class="min-w-0 flex-1">
                        <h2>Record Payment</h2>
                        <p>Money a school paid for its messages — a credit in the Ledger</p>
                    </div>
                    <button type="button" wire:click="closePay" class="sk-back" aria-label="Close"><x-icon name="x-mark" /></button>
                </div>
                <div class="mg-sheet-b space-y-3">
                    <div class="mg-box">
                        <p class="mg-step"><b>1</b> School</p>
                        <select wire:model.live="pOrgId" class="mg-in @error('pOrgId') err @enderror" aria-label="School" @disabled(!$canSchoolPick && $pOrgId !== '')>
                            <option value="">Pick a school…</option>
                            @foreach ($schools as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        @error('pOrgId')<p class="mg-err">{{ $message }}</p>@enderror
                        @if (!empty($payRow))
                            <dl class="sd-figs mt-3" style="--c: 3">
                                <div><dt>Packs cost</dt><dd>{{ $inr($payRow['charged']) }}</dd></div>
                                <div><dt>Paid</dt><dd class="up">{{ $inr($payRow['paid']) }}</dd></div>
                                <div><dt>To pay</dt><dd class="{{ $payRow['due'] > 0 ? 'down' : '' }}">{{ $inr($payRow['due']) }}</dd></div>
                            </dl>
                        @endif
                    </div>

                    @if (!empty($payPacks) && count($payPacks))
                        <div class="mg-box">
                            <p class="mg-step"><b>2</b> For</p>
                            <div class="space-y-2">
                                <label class="mg-opt {{ $pPackId === '' ? 'on' : '' }}" style="flex-direction: row; align-items: center; cursor: pointer; padding-right: 38px">
                                    <input type="radio" wire:model.live="pPackId" value="" class="sr-only"><span class="tick"></span>
                                    <b>What it owes in all</b>
                                </label>
                                @foreach ($payPacks as $pp)
                                    <label class="mg-opt {{ $pPackId === (string) $pp['pack']->id ? 'on' : '' }}" style="flex-direction: row; align-items: center; cursor: pointer; padding-right: 38px" wire:key="pp-{{ $pp['pack']->id }}">
                                        <input type="radio" wire:model.live="pPackId" value="{{ $pp['pack']->id }}" class="sr-only"><span class="tick"></span>
                                        <span class="an-tag {{ $pp['pack']->channel }}">{{ \App\Services\MessagePacks::label($pp['pack']->channel) }}</span>
                                        <b>{{ $pp['pack']->messages ? $num($pp['pack']->messages) . ' messages' : 'Sent before packs' }}</b>
                                        <small class="ml-auto">{{ $pp['pack']->txn_date?->format('d M Y') }} · {{ $inr($pp['left']) }} to pay</small>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mg-box">
                        <p class="mg-step"><b>{{ !empty($payPacks) && count($payPacks) ? 3 : 2 }}</b> Payment</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mg-label" for="p-amount">Amount (₹)</label>
                                <input id="p-amount" wire:model="pAmount" type="number" step="0.01" min="1" placeholder="0.00" class="mg-in @error('pAmount') err @enderror">
                                @error('pAmount')<p class="mg-err">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mg-label" for="p-date">Paid on</label>
                                <input id="p-date" wire:model="pDate" type="date" max="{{ now()->toDateString() }}" class="mg-in @error('pDate') err @enderror">
                                @error('pDate')<p class="mg-err">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mg-label" for="p-mode">Paid by</label>
                                <select id="p-mode" wire:model="pMode" class="mg-in">
                                    @foreach (\App\Services\MessagePacks::MODES as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mg-label" for="p-ref">Reference / UTR</label>
                                <input id="p-ref" wire:model="pReference" type="text" placeholder="Transaction id" class="mg-in @error('pReference') err @enderror">
                                @error('pReference')<p class="mg-err">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="mg-label" for="p-note">Note</label>
                            <textarea id="p-note" wire:model="pNote" rows="2" placeholder="Optional" class="mg-in @error('pNote') err @enderror"></textarea>
                            @error('pNote')<p class="mg-err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
                <div class="mg-sheet-f">
                    <button type="button" wire:click="closePay" class="mg-btn ghost">Cancel</button>
                    <button type="button" wire:click="savePay" wire:loading.attr="disabled" wire:target="savePay" class="mg-btn main" data-pay-save>
                        <span wire:loading.remove wire:target="savePay">Record Payment</span>
                        <span wire:loading wire:target="savePay">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ A SCHOOL'S MESSAGES ══════════ --}}
    @if ($detailOrgId && isset($detail))
        @php $d = $detail; $dr = $d['row']; @endphp
        <div class="mg-sheet-wrap" data-detail-sheet>
            <div class="mg-sheet-back" wire:click="closeDetail"></div>
            <div class="mg-sheet wide">
                <div class="mg-sheet-h">
                    <span class="sd-logo sm">@if ($d['school']?->logo)<img src="{{ $d['school']->logo }}" alt="">@else{{ $initials($d['school']?->name) }}@endif</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate">{{ $d['school']?->name ?? 'School' }}</h2>
                        <p>Messages, packs and payments</p>
                    </div>
                    <button type="button" wire:click="openPay({{ $detailOrgId }})" class="mg-btn line sm">₹ Record Payment</button>
                    <button type="button" wire:click="openGive({{ $detailOrgId }})" class="mg-btn main sm">Give Messages</button>
                    <button type="button" wire:click="closeDetail" class="sk-back" aria-label="Close"><x-icon name="x-mark" /></button>
                </div>
                <div class="mg-sheet-b space-y-4">
                    <div class="mg-chs">
                        @foreach ($C as $ch => $label)
                            @php $pct = $dr['given'][$ch] > 0 ? min(100, round($dr['sent'][$ch] / $dr['given'][$ch] * 100)) : ($dr['sent'][$ch] ? 100 : 0); @endphp
                            <div class="mg-chc">
                                <div class="flex items-center gap-2">
                                    <span class="mg-ch-ic sm {{ $ch }}"><x-icon name="{{ $icons[$ch] }}" /></span>
                                    <p class="text-sm font-semibold text-gray-900">{{ $label }}</p>
                                    <button type="button" wire:click="openSchool({{ $detailOrgId }}, '{{ $ch }}')" class="ml-auto text-xs font-semibold text-teal-700 hover:underline">Open</button>
                                </div>
                                <p class="big mt-3 {{ $dr['left'][$ch] < 0 ? 'neg' : '' }}">{{ $num($dr['left'][$ch]) }}</p>
                                <p class="text-xs text-gray-400 mt-1">left · {{ $num($dr['given'][$ch]) }} given · {{ $num($dr['sent'][$ch]) }} sent</p>
                                <div class="mg-meter {{ $ch }} mt-3"><i class="{{ $dr['left'][$ch] < 0 ? 'over' : '' }}" style="width: {{ $pct }}%"></i></div>
                                <p class="text-xs text-gray-500 mt-2">{{ $num($dr['month'][$ch]) }} this month</p>
                            </div>
                        @endforeach
                    </div>

                    <dl class="sd-figs" style="--c: 4">
                        <div><dt>Packs cost</dt><dd>{{ $inr($dr['charged']) }}</dd></div>
                        <div><dt>Paid</dt><dd class="up">{{ $inr($dr['paid']) }}</dd></div>
                        <div><dt>To pay</dt><dd class="{{ $dr['due'] > 0 ? 'down' : '' }}">{{ $inr($dr['due']) }}</dd></div>
                        <div><dt>To approve</dt><dd>{{ $num($dr['pending']) }}</dd></div>
                    </dl>

                    <div class="sa-card p-5">
                        <h3 class="sa-card-title">Sent by Month</h3>
                        <p class="sa-card-sub">Every channel</p>
                        @include('livewire.super-admin.partials.month-bars', ['values' => $d['monthly']['total'], 'labels' => $d['monthly']['labels'], 'height' => 100, 'empty' => 'Nothing sent in the last six months'])
                    </div>

                    <div class="sa-card overflow-hidden">
                        <div class="px-5 pt-5 pb-3"><h3 class="sa-card-title">Packs</h3></div>
                        <div class="overflow-x-auto">
                            <table class="sk-table">
                                <thead><tr><th>Date</th><th>Channel</th><th class="num">Messages</th><th class="num">Price</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($d['packs'] as $pk)
                                        @php
                                            [$stLabel, $stTone] = $pk->status === 'approved' ? $stateTag($d['state'][$pk->id] ?? null, $pk)
                                                : ($pk->status === 'pending' ? ['To approve', 'amber'] : ['Rejected', 'rose']);
                                        @endphp
                                        <tr wire:key="dp-{{ $pk->id }}">
                                            <td class="whitespace-nowrap text-gray-500">{{ $pk->txn_date?->format('d M Y') }}<span class="block text-[11px] text-gray-400">{{ $pk->source === 'school' ? 'Bought by the school' : ($pk->source === 'carried' ? 'Wallet carried over' : 'Given by ' . ($pk->creator?->name ?? 'SuperLMS')) }}</span></td>
                                            <td><span class="an-tag {{ $pk->channel }}">{{ \App\Services\MessagePacks::label($pk->channel) }}</span></td>
                                            <td class="num font-semibold {{ $pk->type === 'take' ? 'text-rose-600' : '' }}">{{ $pk->type === 'take' ? '−' : '+' }}{{ $num($pk->messages) }}</td>
                                            <td class="num">{{ $pk->type === 'take' ? '—' : ((float) $pk->amount > 0 ? $inr($pk->amount) : 'Free') }}</td>
                                            <td>
                                                <span class="an-tag {{ $stTone }}">{{ $stLabel }}</span>
                                                @if ($pk->decision_note)<span class="block text-xs text-gray-400 mt-0.5">{{ $pk->decision_note }}</span>@endif
                                                @if ($pk->note)<span class="block text-xs text-gray-400 mt-0.5 max-w-xs">{{ $pk->note }}</span>@endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-sm text-gray-400" style="padding: 32px 12px">No packs yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sa-card overflow-hidden">
                        <div class="px-5 pt-5 pb-3"><h3 class="sa-card-title">Payments</h3></div>
                        <div class="overflow-x-auto">
                            <table class="sk-table">
                                <thead><tr><th>Paid on</th><th>For</th><th>Mode</th><th class="num">Amount</th></tr></thead>
                                <tbody>
                                    @forelse ($d['payments'] as $p)
                                        <tr wire:key="dpay-{{ $p->id }}">
                                            <td class="whitespace-nowrap text-gray-500">{{ $p->paid_on?->format('d M Y') }}</td>
                                            <td>@if ($p->pack && $p->pack->messages){{ $num($p->pack->messages) }} {{ \App\Services\MessagePacks::label($p->pack->channel) }}@else<span class="text-gray-400">What it owed</span>@endif</td>
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
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ REJECT ══════════ --}}
    @if ($rejectId)
        <div class="fixed inset-0 flex items-center justify-center z-[9999] px-4 lms-cover" style="background: rgba(12,38,32,.25); backdrop-filter: blur(3px)" wire:click.self="closeReject">
            <div class="bg-white shadow-xl p-6 max-w-sm w-full" style="border-radius: 24px">
                <h3 class="sa-card-title">Reject this request?</h3>
                <p class="sa-card-sub">The school sees the reason on its Messaging page.</p>
                <textarea wire:model="rejectReason" rows="3" placeholder="Payment not received…" class="mg-in mt-4 @error('rejectReason') err @enderror"></textarea>
                @error('rejectReason')<p class="mg-err">{{ $message }}</p>@enderror
                <div class="mt-4 flex items-center gap-2">
                    <button type="button" wire:click="closeReject" class="mg-btn line flex-1">Cancel</button>
                    <button type="button" wire:click="confirmReject" class="mg-btn rose flex-1">Reject</button>
                </div>
            </div>
        </div>
    @endif
</div>
