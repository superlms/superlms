{{-- Super Admin → Calendar → Queries: the website's demo requests (WebsiteDemo) in the
     Fees list's look — S.No, who (the school under the name), how to reach them,
     what they told us, when it came, the meeting and the status, then the ⋮ — with
     a request's slide-in, the Booking settings slide-in (when demos can be booked,
     whose calendar they go on, Google Meet, the message templates) and an email
     preview. Included from livewire.super-admin.calendar (its $statusPill, $gear). --}}
@php
    $reloads = 'qSearch,qStatus,qDays,gotoPage,nextPage,previousPage,clearFilters';
    $th = 'px-3 py-2.5 text-left text-[11px] font-medium text-gray-500 truncate';
    $td = 'px-3 py-3 truncate';
    $actionsFor = function (\App\Models\WebsiteDemo $q): array {
        $id = (int) $q->id;
        $s  = $q->statusKey();
        $open = in_array($s, ['pending', 'booked'], true);

        return array_values(array_filter([
            ['View', "viewQuery({$id})", 'view'],
            $open ? ['Copy booking link', "copyBookingLink({$id})", 'link'] : null,
            $open ? ['Send link again', "resendBookingLink({$id})", 'send'] : null,
            $s !== 'completed' && $s !== 'cancelled' ? ['Mark completed', "setQueryStatus({$id}, 'complete')", 'check'] : null,
            $open ? ['Cancel demo', "setQueryStatus({$id}, 'cancel')", 'cancel'] : null,
            !$open ? ['Reopen', "setQueryStatus({$id}, 'reopen')", 'reopen'] : null,
            ['Delete', "confirmDeleteQuery({$id})", 'delete'],
        ]));
    };
    $meetingCell = function (\App\Models\WebsiteDemo $q): array {
        if ($q->meeting_at) {
            return [$q->meeting_at->format('d M Y'), $q->meeting_at->format('g:i A') . ' – ' . $q->meetingEnd()->format('g:i A'), $q->meet_link];
        }
        if ($q->isCallRequest()) {
            return ['Call ' . $q->preferred_date->format('d M Y'), $q->preferred_time ?: 'Phone call', null];
        }

        return [null, null, null];
    };
@endphp

<div class="p-4 sm:p-6 space-y-4">
    @if (session('google_calendar_ok'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">{{ session('google_calendar_ok') }}</div>
    @elseif (session('google_calendar'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{{ session('google_calendar') }}</div>
    @endif

    {{-- ══════════ DESKTOP TABLE ══════════ --}}
    <div class="hidden md:block rounded-xl border border-gray-200 bg-white shadow-sm" data-queries-table>
        <div class="overflow-x-auto xl:overflow-visible rounded-xl">
            <table class="w-full min-w-[980px] xl:min-w-0 table-fixed text-[13px] text-gray-700">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="pl-4 pr-2 py-2.5 w-14 text-left text-[11px] font-medium text-gray-500 rounded-tl-xl">S.No</th>
                        <th class="{{ $th }} w-[21%]">Name</th>
                        <th class="{{ $th }} w-[19%]">Contact</th>
                        <th class="{{ $th }}">Details</th>
                        <th class="{{ $th }} w-[120px]">Created At</th>
                        <th class="{{ $th }} w-[150px]">Meeting</th>
                        <th class="{{ $th }} w-[104px]">Status</th>
                        <th class="px-2 py-2.5 w-[48px] rounded-tr-xl"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 hidden" wire:loading.delay.long.class.remove="hidden" wire:target="{{ $reloads }}">
                    @include('livewire.super-admin.partials.list-skeleton-rows', ['cols' => ['sno', 'person', 70, 80, 70, 70, 60, 'actions'], 'rows' => 8])
                </tbody>
                <tbody class="divide-y divide-gray-100" wire:loading.delay.long.class="hidden" wire:target="{{ $reloads }}">
                    @forelse ($queries as $index => $q)
                        @php
                            $s = $q->statusKey();
                            [$mDate, $mTime, $mLink] = $meetingCell($q);
                            $details = collect([$q->role, $q->no_of_students ? $q->no_of_students . ' students' : null, $q->city])->filter()->implode(' · ');
                        @endphp
                        <tr wire:key="sa-query-{{ $q->id }}" wire:click="viewQuery({{ $q->id }})" class="hover:bg-gray-50/70 transition-colors cursor-pointer">
                            <td class="pl-4 pr-2 py-3 text-gray-400 tabular-nums">{{ $queries->firstItem() + $index }}</td>
                            <td class="{{ $td }}" title="{{ $q->full_name }}{{ $q->school_name ? ' · ' . $q->school_name : '' }}">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 text-[11px] font-semibold flex items-center justify-center flex-shrink-0">{{ mb_strtoupper(mb_substr(trim((string) $q->full_name) ?: '?', 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 truncate leading-tight">{{ $q->full_name ?: 'Anonymous' }}</p>
                                        <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $q->school_name ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="{{ $td }}">
                                <p class="truncate tabular-nums">{{ $q->phone ?: '—' }}</p>
                                <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $q->email }}</p>
                            </td>
                            <td class="{{ $td }}" title="{{ $details }}">
                                <p class="truncate">{{ $q->role ?: '—' }}</p>
                                <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ collect([$q->no_of_students ? $q->no_of_students . ' students' : null, $q->city])->filter()->implode(' · ') ?: '—' }}</p>
                            </td>
                            <td class="{{ $td }} tabular-nums" title="{{ $q->created_at?->format('l, d M Y g:i A') }}">
                                <p class="truncate">{{ $q->created_at?->format('d M Y') }}</p>
                                <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $q->created_at?->format('g:i A') }}</p>
                            </td>
                            <td class="{{ $td }}">
                                @if ($mDate)
                                    <p class="truncate {{ $s === 'cancelled' ? 'line-through text-gray-400' : '' }}">{{ $mDate }}</p>
                                    <p class="text-[11px] text-gray-400 truncate mt-0.5 flex items-center gap-1">
                                        {{ $mTime }}
                                        @if ($mLink && $s === 'booked')
                                            <a href="{{ $mLink }}" target="_blank" rel="noopener" onclick="event.stopPropagation()" title="{{ $mLink }}" class="text-blue-600 hover:underline font-medium">Join</a>
                                        @endif
                                    </p>
                                @else
                                    <span class="text-gray-400 text-xs">{{ $s === 'pending' ? 'Not booked yet' : '—' }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset {{ $statusPill[$s] }}" data-query-status="{{ $s }}">{{ $q->statusLabel() }}</span>
                            </td>
                            <td class="px-2 py-3" onclick="event.stopPropagation()">
                                @include('livewire.super-admin.partials.list-row-actions', ['active' => true, 'dot' => false, 'actions' => $actionsFor($q)])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-14 text-center">
                                <p class="text-gray-500 text-sm">{{ $filtered ? 'No queries match the filters' : 'No demo requests yet' }}</p>
                                @if ($filtered)
                                    <button wire:click="clearFilters" class="mt-2 text-xs text-gray-900 underline underline-offset-2 font-medium">Clear filters</button>
                                @else
                                    <p class="text-xs text-gray-400 mt-1">Requests from the website's Request Demo form show here.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('livewire.super-admin.partials.list-pagination', ['paginator' => $queries])
    </div>

    {{-- ══════════ MOBILE CARDS ══════════ --}}
    <div class="md:hidden space-y-3">
        @forelse ($queries as $index => $q)
            @php $s = $q->statusKey(); [$mDate, $mTime] = $meetingCell($q); @endphp
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" wire:key="sa-query-m-{{ $q->id }}">
                <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                    <span class="text-xs font-semibold text-gray-400 w-6 text-center tabular-nums flex-shrink-0">{{ $queries->firstItem() + $index }}</span>
                    <div class="min-w-0 flex-1" wire:click="viewQuery({{ $q->id }})">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $q->full_name ?: 'Anonymous' }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $q->school_name ?: '—' }}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset {{ $statusPill[$s] }}">{{ $q->statusLabel() }}</span>
                    @include('livewire.super-admin.partials.list-row-actions', ['active' => true, 'dot' => false, 'actions' => $actionsFor($q)])
                </div>
                <div class="px-4 py-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs" wire:click="viewQuery({{ $q->id }})">
                    <div><p class="text-gray-400">Phone</p><p class="text-gray-800 truncate">{{ $q->phone ?: '—' }}</p></div>
                    <div><p class="text-gray-400">Created At</p><p class="text-gray-800 truncate">{{ $q->created_at?->format('d M, g:i A') }}</p></div>
                    <div class="col-span-2"><p class="text-gray-400">Meeting</p><p class="text-gray-800 truncate">{{ $mDate ? $mDate . ' · ' . $mTime : ($s === 'pending' ? 'Not booked yet' : '—') }}</p></div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-sm text-gray-500">{{ $filtered ? 'No queries match the filters' : 'No demo requests yet' }}</div>
        @endforelse
        @if ($queries->hasPages())
            <div class="bg-white rounded-xl border border-gray-200">@include('livewire.super-admin.partials.list-pagination', ['paginator' => $queries])</div>
        @endif
    </div>
</div>

{{-- ══════════════════════════ A REQUEST'S SLIDE-IN ══════════════════════════ --}}
@if ($viewing)
    @php
        $s = $viewing->statusKey();
        $open = in_array($s, ['pending', 'booked'], true);
        $digits = preg_replace('/\D+/', '', (string) $viewing->phone);
        $wa = \App\Services\WhatsAppService::normalizePhone($viewing->phone);
        $row = fn ($label, $value) => $value !== null && $value !== '' ? [$label, $value] : null;
        $msgLabel = ['thanks' => 'Thank-you with booking link', 'confirmed' => 'Booking confirmed', 'moved' => 'New time confirmed'];
    @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data x-on:keydown.escape.window="$wire.closeQuery()">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeQuery"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col" data-query-panel>
            <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewing->full_name ?: 'Demo request' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $viewing->school_name ?: 'Website demo request' }} · {{ $viewing->created_at?->format('d M Y, g:i A') }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset {{ $statusPill[$s] }}">{{ $viewing->statusLabel() }}</span>
                    <button type="button" wire:click="closeQuery" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 text-sm">
                {{-- The meeting --}}
                <div class="rounded-xl border {{ $s === 'booked' ? 'border-blue-200 bg-blue-50/60' : 'border-gray-200 bg-gray-50' }} p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $s === 'booked' ? 'text-blue-700' : 'text-gray-500' }}">Meeting</p>
                    @if ($viewing->meeting_at)
                        <p class="mt-1 font-semibold text-gray-900 {{ $s === 'cancelled' ? 'line-through' : '' }}">{{ $viewing->meetingLabel() }}</p>
                        @if ($viewing->meet_link)
                            <p class="mt-1 text-xs break-all">
                                <span class="text-gray-500">{{ $viewing->meet_provider === 'google' ? 'Google Meet' : 'Jitsi Meet' }}:</span>
                                <a href="{{ $viewing->meet_link }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ $viewing->meet_link }}</a>
                            </p>
                        @endif
                        @if ($viewing->booked_at)
                            <p class="mt-1 text-[11px] text-gray-400">Picked by them {{ $viewing->booked_at->format('d M, g:i A') }}</p>
                        @endif
                    @elseif ($viewing->isCallRequest())
                        <p class="mt-1 font-semibold text-gray-900">Call on {{ $viewing->preferred_date->format('D, d M Y') }}@if ($viewing->preferred_time) · {{ $viewing->preferred_time }}@endif</p>
                        <p class="mt-1 text-xs text-gray-500">Asked for a phone call from the website's Schedule a Call.</p>
                    @else
                        <p class="mt-1 text-gray-600">{{ $open ? 'Not booked yet — waiting for them to pick a time.' : 'No meeting.' }}</p>
                    @endif
                </div>

                {{-- Booking link --}}
                @if ($open)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Booking link</p>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ $viewing->bookingUrl() }}" class="flex-1 min-w-0 text-xs bg-gray-50 border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-600" onclick="this.select()">
                            <button type="button" x-on:click="navigator.clipboard && navigator.clipboard.writeText(@js($viewing->bookingUrl())); window.lmsToast && window.lmsToast({ type: 'success', message: 'Booking link copied.' })"
                                class="px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50 flex-shrink-0">Copy</button>
                            <a href="{{ $viewing->bookingUrl() }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50 flex-shrink-0">Open</a>
                        </div>
                    </div>
                @endif

                {{-- Details --}}
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Details</p>
                    <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200">
                        @foreach (array_filter([
                            $row('Name', $viewing->full_name),
                            $row('Role', $viewing->role),
                            $row('School', $viewing->school_name),
                            $row('City', $viewing->city),
                            $row('Students', $viewing->no_of_students),
                        ]) as [$label, $value])
                            <div class="flex items-start justify-between gap-4 px-3 py-2">
                                <dt class="text-gray-500 flex-shrink-0">{{ $label }}</dt>
                                <dd class="text-gray-900 text-right break-words min-w-0">{{ $value }}</dd>
                            </div>
                        @endforeach
                        @if ($viewing->phone)
                            <div class="flex items-start justify-between gap-4 px-3 py-2">
                                <dt class="text-gray-500">Phone</dt>
                                <dd class="text-right">
                                    <a href="tel:{{ $digits }}" class="text-blue-600 hover:underline tabular-nums">{{ $viewing->phone }}</a>
                                    @if ($wa)
                                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="ml-2 text-emerald-600 hover:underline">WhatsApp</a>
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if ($viewing->email)
                            <div class="flex items-start justify-between gap-4 px-3 py-2">
                                <dt class="text-gray-500">Email</dt>
                                <dd class="text-right break-all"><a href="mailto:{{ $viewing->email }}" class="text-blue-600 hover:underline">{{ $viewing->email }}</a></dd>
                            </div>
                        @endif
                        <div class="flex items-start justify-between gap-4 px-3 py-2">
                            <dt class="text-gray-500">Created At</dt>
                            <dd class="text-gray-900 text-right">{{ $viewing->created_at?->format('D, d M Y · g:i A') }}</dd>
                        </div>
                        @if ($viewing->remark)
                            <div class="px-3 py-2">
                                <dt class="text-gray-500 mb-0.5">Remark</dt>
                                <dd class="text-gray-900 whitespace-pre-line break-words">{{ $viewing->remark }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- What was sent --}}
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Messages sent</p>
                    @forelse (array_reverse((array) ($viewing->messages ?? [])) as $m)
                        <div class="rounded-lg border border-gray-200 px-3 py-2 mb-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-gray-800 text-xs">{{ $msgLabel[$m['type'] ?? ''] ?? ucfirst((string) ($m['type'] ?? 'Message')) }}</p>
                                <p class="text-[11px] text-gray-400">{{ isset($m['at']) ? \Carbon\Carbon::parse($m['at'])->format('d M, g:i A') : '' }}</p>
                            </div>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-[11px]">
                                @foreach (['email' => 'Email', 'whatsapp' => 'WhatsApp'] as $ch => $chLabel)
                                    @php $ok = ($m[$ch] ?? null) === 'ok'; @endphp
                                    <span class="{{ $ok ? 'text-emerald-600' : 'text-red-500' }}" title="{{ $ok ? 'Sent' : ($m[$ch] ?? '') }}">
                                        {{ $ok ? '✓' : '✗' }} {{ $chLabel }}@unless ($ok) <span class="text-gray-400">— {{ \Illuminate\Support\Str::limit((string) ($m[$ch] ?? 'not sent'), 60) }}</span>@endunless
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">Nothing sent yet{{ $viewing->isCallRequest() ? ' (a call request gets no booking link unless you send it)' : '' }}.</p>
                    @endforelse
                </div>
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex flex-wrap items-center gap-2 flex-shrink-0">
                <button type="button" wire:click="confirmDeleteQuery({{ $viewing->id }})" class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
                    @if ($open)
                        <button type="button" wire:click="setQueryStatus({{ $viewing->id }}, 'cancel')" wire:loading.attr="disabled"
                            class="px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel demo</button>
                        <button type="button" wire:click="resendBookingLink({{ $viewing->id }})" wire:loading.attr="disabled" wire:target="resendBookingLink"
                            class="px-3 py-2 text-sm font-medium border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-md">Send link again</button>
                        <button type="button" wire:click="setQueryStatus({{ $viewing->id }}, 'complete')" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Mark completed</button>
                    @else
                        <button type="button" wire:click="setQueryStatus({{ $viewing->id }}, 'reopen')" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Reopen</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ══════════════════════════ BOOKING SETTINGS SLIDE-IN ══════════════════════════ --}}
@if ($showSettings)
    @php $input = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500'; @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data x-on:keydown.escape.window="$wire.closeSettings()">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSettings"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col" data-booking-settings>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Booking Settings</h2>
                    <p class="text-xs text-gray-500 mt-0.5">When website demos can be booked, and what is sent</p>
                </div>
                <button type="button" wire:click="closeSettings" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form wire:submit="saveSettings" class="flex-1 overflow-y-auto px-6 py-5 space-y-5" id="booking-settings-form">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Days open for demos <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-7 gap-1.5">
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $wd)
                            <label class="cursor-pointer">
                                <input type="checkbox" wire:model="sDays" value="{{ $i }}" class="peer sr-only">
                                <span class="block text-center text-xs font-semibold py-2 rounded-md border border-gray-200 text-gray-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-gray-50">{{ $wd }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('sDays')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">From <span class="text-red-500">*</span></label>
                        <input wire:model="sStart" type="time" class="{{ $input }}">
                        @error('sStart')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">To <span class="text-red-500">*</span></label>
                        <input wire:model="sEnd" type="time" class="{{ $input }}">
                        @error('sEnd')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Demo length</label>
                        <select wire:model="sMinutes" class="{{ $input }}">
                            @foreach (\App\Support\DemoBooking::LENGTHS as $m)
                                <option value="{{ $m }}">{{ $m }} min</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Book ahead</label>
                        <select wire:model="sDaysAhead" class="{{ $input }}">
                            @foreach ([7, 14, 21, 30, 45, 60] as $d)
                                <option value="{{ $d }}">{{ $d }} days</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notice</label>
                        <select wire:model="sNotice" class="{{ $input }}">
                            @foreach ([0, 1, 2, 3, 6, 12, 24, 48] as $h)
                                <option value="{{ $h }}">{{ $h === 0 ? 'None' : $h . ' hr' . ($h > 1 ? 's' : '') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Put booked demos on the calendar of</label>
                    <select wire:model="sOwner" class="{{ $input }}">
                        @foreach ($ownerChoices as $u)
                            <option value="{{ $u->id }}">{{ $u->name ?: $u->email }}{{ $u->role === 'super-admin' ? ' (Super Admin)' : '' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">Their own meetings and events at that time are kept free.</p>
                </div>

                {{-- Google Meet --}}
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">Meeting links</p>
                            @if ($googleReady)
                                <p class="text-xs text-emerald-700 mt-0.5">Google Meet · connected{{ $googleEmail ? ' as ' . $googleEmail : '' }}</p>
                                <p class="text-[11px] text-gray-400 mt-1">Each booked demo goes on that Google Calendar with its own Meet link, the visitor invited.</p>
                            @else
                                <p class="text-xs text-amber-700 mt-0.5">Jitsi Meet (no Google account connected)</p>
                                <p class="text-[11px] text-gray-400 mt-1">
                                    @if ($googleSetUp)
                                        Connect a Google account to give every booked demo a Google Meet link instead.
                                    @else
                                        For Google Meet links, add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to the server env (DEMO_BOOKING.md), then connect.
                                    @endif
                                </p>
                            @endif
                        </div>
                        @if ($isMainAdmin && $googleSetUp)
                            @if ($googleReady)
                                <button type="button" wire:click="disconnectGoogle" wire:confirm="Disconnect Google? New demos will get a Jitsi Meet link."
                                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50 flex-shrink-0">Disconnect</button>
                            @else
                                <a href="{{ route('super-admin.google.connect') }}"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-600 hover:bg-blue-700 text-white flex-shrink-0">Connect Google</a>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Templates --}}
                <div>
                    <p class="text-sm font-semibold text-gray-900">Message templates</p>
                    <p class="text-[11px] text-gray-400 mt-0.5 mb-3">The emails are ready. Each WhatsApp one is sent once Meta approves a template with this name and text (WhatsApp Manager → Message templates).</p>

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <button type="button" wire:click="showEmailPreview('thanks')" class="text-left rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50">
                            <p class="text-xs font-semibold text-gray-800">Email · Thank you</p>
                            <p class="text-[11px] text-blue-600">Preview</p>
                        </button>
                        <button type="button" wire:click="showEmailPreview('confirmed')" class="text-left rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50">
                            <p class="text-xs font-semibold text-gray-800">Email · Booking confirmed</p>
                            <p class="text-[11px] text-blue-600">Preview</p>
                        </button>
                    </div>

                    @foreach ($templates as $t)
                        <div class="rounded-lg border border-gray-200 mb-2 overflow-hidden" x-data="{ open: false }">
                            <button type="button" x-on:click="open = !open" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-gray-50">
                                <span class="min-w-0">
                                    <span class="block text-xs font-semibold text-gray-800">WhatsApp · <span class="font-mono">{{ $t['name'] }}</span></span>
                                    <span class="block text-[11px] text-gray-400 truncate">{{ $t['when'] }}</span>
                                </span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div x-show="open" x-cloak class="border-t border-gray-100 px-3 py-2.5 space-y-2 text-[11px] text-gray-600">
                                <p><span class="text-gray-400">Category:</span> {{ $t['category'] }} · <span class="text-gray-400">Language:</span> English</p>
                                <div>
                                    <div class="flex items-center justify-between"><span class="text-gray-400">Body</span>
                                        <button type="button" x-on:click="navigator.clipboard && navigator.clipboard.writeText(@js($t['body']))" class="text-blue-600 hover:underline">Copy</button></div>
                                    <pre class="mt-1 whitespace-pre-wrap font-sans text-xs text-gray-800 bg-gray-50 rounded-md px-2.5 py-2">{{ $t['body'] }}</pre>
                                </div>
                                <p><span class="text-gray-400">Samples:</span> @foreach ($t['samples'] as $i => $sample)<span class="font-mono">{{ str_repeat('{', 2) . ($i + 1) . str_repeat('}', 2) }}</span> {{ $sample }}@if (!$loop->last) · @endif @endforeach</p>
                                <p class="break-all"><span class="text-gray-400">Button:</span> Visit website · “{{ $t['button'][0] }}” · Dynamic URL <span class="font-mono">{{ $t['button'][1] }}</span></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" wire:click="closeSettings" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="submit" form="booking-settings-form" wire:loading.attr="disabled" wire:target="saveSettings"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveSettings">Save</span>
                    <span wire:loading wire:target="saveSettings">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════════════════════ EMAIL PREVIEW ══════════════════════════ --}}
@if ($previewHtml)
    <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closePreview()">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="closePreview"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl h-[85vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">{{ $previewEmail === 'confirmed' ? 'Email · Booking confirmed' : 'Email · Thank you' }} <span class="font-normal text-gray-400">— sample</span></p>
                <button type="button" wire:click="closePreview" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <iframe srcdoc="{{ $previewHtml }}" class="flex-1 w-full bg-gray-100" sandbox title="Email preview"></iframe>
        </div>
    </div>
@endif
