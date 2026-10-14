{{-- WhatsApp: one month of a school's messages — how many of each kind went
     and what the charged ones come to (App\Services\SchoolWhatsApp; a message
     Meta could not deliver is neither counted nor charged).
       $wa         SchoolWhatsApp::summary() — months, month, usage, switches, rate
       $waEditable the Super Admin's: Absent alert / Fee received switches and
                   the full card; without it the school admin's strip, to read.
     The month is the component's waMonth. --}}
@php
    $waUsage = $wa['usage'];
    $waMoney = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $waRate  = $waMoney($wa['rate']);
    $waTiles = [];
    foreach (\App\Services\SchoolWhatsApp::FEATURES as $waFeature => [$waLabel, $waCharged, $waSwitched]) {
        $waTiles[] = [
            'feature' => $waFeature,
            'label'   => $waLabel,
            'count'   => (int) ($waUsage['counts'][$waFeature] ?? 0),
            'sub'     => match (true) {
                $waSwitched => (($wa['switches'][$waFeature] ?? false) ? 'On' : 'Off') . ' · ' . $waRate . ' each',
                $waCharged  => 'Send Credentials · ' . $waRate . ' each',
                default     => 'On adding someone',
            },
        ];
    }
    $waFailed = (int) $waUsage['failed'];
@endphp

@if ($waEditable ?? false)
    <section>
        <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">WhatsApp</h2>
            <select wire:model.live="waMonth" aria-label="Month"
                class="text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700">
                @foreach ($wa['months'] as $waKey => $waName)
                    <option value="{{ $waKey }}" @selected($waKey === $wa['month'])>{{ $waName }}</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- The paid messages the school has on: off until turned on here. --}}
            <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-2">
                @foreach ($wa['switches'] as $waFeature => $waOn)
                    <div class="flex items-center gap-2.5" wire:key="wa-switch-{{ $waFeature }}">
                        <button type="button" role="switch" aria-checked="{{ $waOn ? 'true' : 'false' }}"
                            wire:click="toggleWhatsApp('{{ $waFeature }}')" wire:loading.attr="disabled"
                            title="{{ $waOn ? 'Turn off' : 'Turn on' }}"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-60 {{ $waOn ? 'bg-emerald-500' : 'bg-gray-300' }}">
                            <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $waOn ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                        </button>
                        <span class="text-sm font-medium text-gray-800">{{ \App\Services\SchoolWhatsApp::FEATURES[$waFeature][0] }}</span>
                        <span class="text-xs {{ $waOn ? 'text-emerald-600' : 'text-gray-400' }}">{{ $waOn ? 'On' : 'Off' }}</span>
                    </div>
                @endforeach
                <span class="text-xs text-gray-400 sm:ml-auto">{{ $waRate }} a message · not charged when it is not delivered</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5">
                @foreach ($waTiles as $waTile)
                    <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $waTile['label'] }}</p>
                        <p class="text-[26px] leading-none font-semibold text-gray-900 mt-2 tabular-nums truncate">{{ number_format($waTile['count']) }}</p>
                        <p class="text-xs text-gray-400 mt-1.5 truncate">{{ $waTile['sub'] }}</p>
                    </div>
                @endforeach
                <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0">
                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">Total · {{ $wa['months'][$wa['month']] }}</p>
                    <p class="text-[26px] leading-none font-semibold text-gray-900 mt-2 tabular-nums truncate">{{ $waMoney($waUsage['amount']) }}</p>
                    <p class="text-xs text-gray-400 mt-1.5 truncate">
                        {{ number_format($waUsage['charged']) }} charged{{ $waFailed ? ' · ' . number_format($waFailed) . ' not delivered' : '' }}
                    </p>
                </div>
            </div>
        </div>
    </section>
@else
    {{-- The school admin's: the same figures in one strip, to read. --}}
    <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap items-center gap-x-5 gap-y-2">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </span>
            <span class="text-sm font-semibold text-gray-800">WhatsApp</span>
            <select wire:model.live="waMonth" aria-label="Month"
                class="text-xs bg-white border border-gray-200 rounded-md pl-2 pr-7 py-1 text-gray-700">
                @foreach ($wa['months'] as $waKey => $waName)
                    <option value="{{ $waKey }}" @selected($waKey === $wa['month'])>{{ $waName }}</option>
                @endforeach
            </select>
        </div>
        @foreach ($waTiles as $waTile)
            <span class="text-xs text-gray-500 whitespace-nowrap" title="{{ $waTile['sub'] }}">
                {{ $waTile['label'] }}: <strong class="text-gray-800 tabular-nums">{{ number_format($waTile['count']) }}</strong>
                @if (array_key_exists($waTile['feature'], $wa['switches']))
                    <span class="{{ $wa['switches'][$waTile['feature']] ? 'text-emerald-600' : 'text-gray-400' }}">({{ $wa['switches'][$waTile['feature']] ? 'On' : 'Off' }})</span>
                @endif
            </span>
        @endforeach
        <span class="text-xs text-gray-500 whitespace-nowrap sm:ml-auto" title="{{ $waRate }} a message; a message not delivered is not charged">
            Total: <strong class="text-gray-900 tabular-nums">{{ $waMoney($waUsage['amount']) }}</strong>
            <span class="text-gray-400">· {{ number_format($waUsage['charged']) }} charged</span>
        </span>
    </div>
@endif
