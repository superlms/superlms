{{-- WhatsApp analytics of one school (App\Livewire\Concerns\HandlesWhatsAppAnalytics):
     the last 30 days or a month, how many of each kind went day by day, what
     the charged ones came to (a message not delivered is not charged), how
     far they got, and the messages themselves.
       $wa         HandlesWhatsAppAnalytics::whatsAppAnalytics()
       $waEditable the Super Admin's: Absent alert / Fee received switches
       $waKey      keeps this page's chart apart from another's --}}
@php
    $waFeatures = \App\Services\SchoolWhatsApp::FEATURES;
    $waUsage    = $wa['usage'];
    $waDaily    = $wa['daily'];
    $waMoney    = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $waRate     = $waMoney($wa['rate']);
    $waWent     = array_sum($waUsage['counts']);
    $waTried    = $waWent + $waUsage['failed'];
    $waColors   = [
        'absent_alert' => ['rgba(244,63,94,0.75)',  'bg-rose-500'],
        'fee_received' => ['rgba(16,185,129,0.75)', 'bg-emerald-500'],
        'credentials'  => ['rgba(99,102,241,0.75)', 'bg-indigo-500'],
        'welcome'      => ['rgba(148,163,184,0.8)', 'bg-slate-400'],
    ];

    $waTiles = [['Messages', number_format($waWent), $wa['label']]];
    foreach ($waFeatures as $waF => [$waLabel, $waCharged, $waSwitched]) {
        $waTiles[] = [$waLabel, number_format($waUsage['counts'][$waF] ?? 0), match (true) {
            $waSwitched => (($wa['switches'][$waF] ?? false) ? 'On' : 'Off') . ' · ' . $waRate . ' each',
            $waCharged  => 'Send Credentials · ' . $waRate . ' each',
            default     => 'On adding someone',
        }];
    }
    $waTiles[] = ['Charged', $waMoney($waUsage['amount']), number_format($waUsage['charged']) . ' charged' . ($waUsage['failed'] ? ' · ' . number_format($waUsage['failed']) . ' not delivered' : '')];

    $waDatasets = [];
    foreach ($waFeatures as $waF => [$waLabel]) {
        $waDatasets[] = ['label' => $waLabel, 'data' => $waDaily['counts'][$waF], 'backgroundColor' => $waColors[$waF][0], 'borderRadius' => 2, 'maxBarThickness' => 22, 'stack' => 'went'];
    }
    $waDays     = max(1, count($waDaily['days']));
    $waBusiest  = $waDaily['total'] ? array_keys($waDaily['total'], max($waDaily['total']))[0] : null;

    $waStatuses = [
        'read'      => ['Read',          'bg-emerald-500', 'text-emerald-700 bg-emerald-50'],
        'delivered' => ['Delivered',     'bg-blue-500',    'text-blue-700 bg-blue-50'],
        'sent'      => ['Sent',          'bg-gray-400',    'text-gray-600 bg-gray-100'],
        'failed'    => ['Not delivered', 'bg-red-500',     'text-red-700 bg-red-50'],
    ];
    $waList = $wa['messages'];
@endphp

<div class="space-y-6">
    {{-- ── Toolbar: the paid messages on / off (Super Admin), and the period ── --}}
    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
        @if ($waEditable ?? false)
            @foreach ($wa['switches'] as $waF => $waOn)
                <div class="flex items-center gap-2.5" wire:key="wa-switch-{{ $waF }}">
                    <button type="button" role="switch" aria-checked="{{ $waOn ? 'true' : 'false' }}"
                        wire:click="toggleWhatsApp('{{ $waF }}')" wire:loading.attr="disabled"
                        title="{{ $waOn ? 'Turn off' : 'Turn on' }}"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-60 {{ $waOn ? 'bg-emerald-500' : 'bg-gray-300' }}">
                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $waOn ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                    </button>
                    <span class="text-sm font-medium text-gray-800">{{ $waFeatures[$waF][0] }}</span>
                    <span class="text-xs {{ $waOn ? 'text-emerald-600' : 'text-gray-400' }}">{{ $waOn ? 'On' : 'Off' }}</span>
                </div>
            @endforeach
        @else
            <p class="text-xs text-gray-500">
                @foreach ($wa['switches'] as $waF => $waOn)
                    {{ $waFeatures[$waF][0] }}: <strong class="{{ $waOn ? 'text-emerald-600' : 'text-gray-400' }}">{{ $waOn ? 'On' : 'Off' }}</strong>@if (!$loop->last)<span class="text-gray-300"> · </span>@endif
                @endforeach
            </p>
        @endif
        <span class="text-xs text-gray-400">{{ $waRate }} a message · not charged when it is not delivered</span>
        <select wire:model.live="waPeriod" aria-label="Period"
            class="sm:ml-auto text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700">
            @foreach ($wa['periods'] as $waKeyP => $waName)
                <option value="{{ $waKeyP }}" @selected($waKeyP === $wa['period'])>{{ $waName }}</option>
            @endforeach
        </select>
    </div>

    {{-- ── Tiles ── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ($waTiles as [$waLabel, $waValue, $waSub])
            <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $waLabel }}</p>
                <p class="text-[26px] leading-none font-semibold text-gray-900 mt-2 tabular-nums truncate">{{ $waValue }}</p>
                <p class="text-xs text-gray-400 mt-1.5 truncate">{{ $waSub }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── Day by day, and how far they got ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Messages a Day</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $wa['label'] }} · {{ number_format($waWent / $waDays, 1) }} a day on average</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-400">
                    @foreach ($waFeatures as $waF => [$waLabel])
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm {{ $waColors[$waF][1] }} inline-block"></span> {{ $waLabel }}</span>
                    @endforeach
                </div>
            </div>
            {{-- Keyed on its figures: another period or a new message redraws it. --}}
            <div class="h-64" wire:key="wa-daily-{{ $waKey ?? 'school' }}-{{ md5(json_encode([$waDaily['days'], $waDaily['counts']])) }}">
                <canvas x-data="{
                    chart: null,
                    init() {
                        if (typeof Chart === 'undefined') return;
                        this.chart = new Chart(this.$el.getContext('2d'), {
                            type: 'bar',
                            data: { labels: @js($waDaily['labels']), datasets: @js($waDatasets) },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { filter: (i) => i.raw > 0, callbacks: { footer: (items) => 'Total: ' + items.reduce((s, i) => s + i.raw, 0) } }
                                },
                                scales: {
                                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8 } },
                                    y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, precision: 0 } }
                                }
                            }
                        });
                    },
                    destroy() { this.chart?.destroy(); }
                }"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-800 mb-4">Delivery</h3>
            <dl class="space-y-4 text-sm">
                @foreach ($waStatuses as $waS => [$waLabel, $waBar])
                    @php
                        $waN     = (int) ($waUsage['statuses'][$waS] ?? 0);
                        $waShare = $waTried > 0 ? round($waN / $waTried * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-gray-500">{{ $waLabel }}</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($waN) }} <span class="text-xs font-normal text-gray-400">({{ $waShare }}%)</span></dd>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2"><div class="{{ $waBar }} h-1.5 rounded-full" style="width: {{ $waShare }}%"></div></div>
                    </div>
                @endforeach
                <div class="flex items-baseline justify-between gap-3 pt-4 border-t border-gray-100">
                    <dt class="text-gray-500">Busiest day</dt>
                    <dd class="font-semibold text-gray-900 tabular-nums">
                        @if ($waBusiest !== null && $waDaily['total'][$waBusiest] > 0)
                            {{ $waDaily['labels'][$waBusiest] }} · {{ number_format($waDaily['total'][$waBusiest]) }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ── The messages ── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3">
            <h3 class="text-sm font-semibold text-gray-800">Messages · {{ $wa['label'] }}</h3>
            <span class="text-xs text-gray-400 tabular-nums">{{ number_format($waList['count']) }}</span>
            <div class="sm:ml-auto flex flex-wrap items-center gap-2">
                <select wire:model.live="waFeature" aria-label="Kind"
                    class="text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700">
                    <option value="">All kinds</option>
                    @foreach ($waFeatures as $waF => [$waLabel])
                        <option value="{{ $waF }}" @selected(($waFeature ?? '') === $waF)>{{ $waLabel }}</option>
                    @endforeach
                </select>
                <select wire:model.live="waStatus" aria-label="Status"
                    class="text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700">
                    <option value="">All statuses</option>
                    @foreach ($waStatuses as $waS => [$waLabel])
                        <option value="{{ $waS }}" @selected(($waStatus ?? '') === $waS)>{{ $waLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($waList['count'] === 0)
            <p class="text-center py-12 text-sm text-gray-400">No messages in {{ $wa['period'] === '' ? 'the last 30 days' : $wa['label'] }}{{ ($waFeature ?? '') !== '' || ($waStatus ?? '') !== '' ? ' for this filter' : '' }}.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[720px]">
                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-2.5 text-left font-medium w-12">S.No</th>
                            <th class="px-3 py-2.5 text-left font-medium">Date</th>
                            <th class="px-3 py-2.5 text-left font-medium">Kind</th>
                            <th class="px-3 py-2.5 text-left font-medium">To</th>
                            <th class="px-3 py-2.5 text-left font-medium">Status</th>
                            <th class="px-5 py-2.5 text-right font-medium">Charge</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($waList['rows'] as $waI => $waRow)
                            <tr class="hover:bg-gray-50/70" wire:key="wa-msg-{{ $waRow['id'] }}">
                                <td class="px-5 py-2.5 text-gray-400 tabular-nums">{{ $waI + 1 }}</td>
                                <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap">
                                    {{ $waRow['at']?->format('d M Y') }}
                                    <span class="block text-xs text-gray-400">{{ $waRow['at']?->format('h:i A') }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full {{ $waColors[$waRow['feature']][1] ?? 'bg-gray-400' }}"></span>{{ $waRow['label'] }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="block text-gray-800 truncate max-w-[14rem]">{{ $waRow['name'] ?: '—' }}</span>
                                    <span class="block text-xs text-gray-400 tabular-nums">{{ $waRow['phone'] }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex px-2 py-0.5 text-[11px] rounded-full font-medium {{ $waStatuses[$waRow['status']][2] ?? 'text-gray-600 bg-gray-100' }}"
                                        @if ($waRow['status'] === 'failed' && $waRow['error']) title="{{ $waRow['error'] }}" @endif>{{ $waStatuses[$waRow['status']][0] ?? ucfirst($waRow['status']) }}</span>
                                </td>
                                <td class="px-5 py-2.5 text-right whitespace-nowrap tabular-nums {{ is_float($waRow['charge']) ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                    {{ match (true) { $waRow['charge'] === 'free' => 'Free', $waRow['charge'] === 'none' => 'Not charged', default => $waMoney($waRow['charge']) } }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($waList['count'] > count($waList['rows']))
                <div class="px-5 py-3 border-t border-gray-100 text-center">
                    <button wire:click="showMoreWhatsApp" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Show more ({{ number_format($waList['count'] - count($waList['rows'])) }} more)
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
