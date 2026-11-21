<div class="sa-skin sa-home min-h-screen">
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')

    {{-- Super Admin → Session in the Home's analytics look (2026-11-21): every
         screen — the sessions, a session's schools, a school's data in it, the
         Add/Edit panel and Make current — white cards, teal, Home's header. --}}
    <style>
        .ss-mc { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 12px; border-radius: 999px; border: 1px solid #e3e8e6; background: #fff; color: #44544f; font-size: 12.5px; font-weight: 600; white-space: nowrap; transition: background .2s, border-color .2s, color .2s; }
        .ss-mc:hover { border-color: var(--sk-teal); color: var(--sk-teal-700); background: var(--sk-teal-50); }
        .ss-mc:disabled { opacity: .6; cursor: wait; }
        .ss-mc svg { width: 14px; height: 14px; }
        .ss-cur { display: inline-flex; align-items: center; gap: 5px; height: 26px; padding: 0 10px; border-radius: 999px; background: var(--sk-teal); color: #fff; font-size: 12px; font-weight: 600; }
        .ss-cur svg { width: 13px; height: 13px; }
        .ss-ic { width: 36px; height: 36px; border-radius: 12px; flex: none; display: grid; place-items: center; background: #f3f6f5; color: #6b7c76; }
        .ss-ic.on { background: var(--sk-teal); color: #fff; }
        .ss-ic svg { width: 17px; height: 17px; }

        /* Make current: the two codes, each verified on its own. */
        .ss-otp { border: 1px solid #eef1f0; border-radius: 18px; padding: 14px; transition: border-color .2s, background .2s; }
        .ss-otp.done { border-color: #b1e6db; background: #f5fcfa; }
        .ss-otp-ic { width: 36px; height: 36px; border-radius: 12px; flex: none; display: grid; place-items: center; background: var(--sk-teal-50); color: var(--sk-teal-700); }
        .ss-otp.done .ss-otp-ic { background: var(--sk-teal); color: #fff; }
        .ss-otp-ic svg { width: 18px; height: 18px; }
        .ss-code { flex: 1; min-width: 0; height: 44px; padding: 0 14px; border-radius: 999px; border: 1px solid #d6dedb; background: #fff; text-align: center; font-size: 18px; font-weight: 600; letter-spacing: .45em; font-variant-numeric: tabular-nums; color: var(--sk-ink); outline: none; transition: border-color .2s, box-shadow .2s; }
        .ss-code:focus { border-color: var(--sk-teal); box-shadow: 0 0 0 4px rgba(13,148,136,.12); }
        .ss-code.bad { border-color: #fda4af; background: #fff7f8; }
        .ss-step { display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #f3f6f5; color: #6b7c76; }
        .ss-step.done { background: var(--sk-teal-50); color: var(--sk-teal-700); }
        .ss-step svg { width: 13px; height: 13px; }
        .ss-field { width: 100%; height: 42px; padding: 0 16px; border-radius: 999px; border: 1px solid #d6dedb; background: #fff; font-size: 14px; color: var(--sk-ink); outline: none; transition: border-color .2s, box-shadow .2s; }
        .ss-field:focus { border-color: var(--sk-teal); box-shadow: 0 0 0 4px rgba(13,148,136,.12); }
    </style>

    @php
        $current = $sessions->firstWhere('is_current', true);
        $num     = fn ($v) => number_format((float) $v);
        $pctOf   = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100, 1) : 0;
        $statusChip = [
            'running'   => ['Running', 'green'],
            'upcoming'  => ['Upcoming', 'amber'],
            'completed' => ['Completed', ''],
        ];
        $chevron = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
        $backIcon = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>';
        $lockIcon = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>';
        $tickIcon = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    @endphp

    @if (!$open)
        {{-- ══════════════════════ THE SESSIONS ══════════════════════ --}}
        @php
            $byStatus = $sessions->groupBy(fn ($s) => $s->status());
            $curCount = $current ? ($counts[$current->id] ?? ['total' => 0, 'new' => 0]) : null;
            if ($current) {
                $span    = (int) max(1, abs((int) $current->starts_on->copy()->startOfDay()->diffInDays($current->ends_on->copy()->startOfDay())) + 1);
                $gone    = (int) max(0, min($span, (int) $current->starts_on->copy()->startOfDay()->diffInDays(now()->startOfDay(), false) + 1));
                $daysPct = round($gone / $span * 100);
                $left    = max(0, $span - $gone);
            }
            $perSession = $sessions->sortBy('starts_on')->map(fn ($s) => ['label' => $s->name, 'count' => $counts[$s->id]['total'] ?? 0])->values()->all();
        @endphp

        <div class="sk-head">
            <div class="sk-title">
                <h1>Session</h1>
                <p class="hidden sm:block">The academic sessions and the current one</p>
            </div>
            <div class="sk-right">
                <span class="sk-pill" style="height: 36px; padding: 0 14px">Current: <b style="color: var(--sk-teal-700)">{{ $current?->name ?? '—' }}</b></span>
                <button type="button" wire:click="openCreate" class="sk-cta">
                    <x-icon name="plus" class="w-4 h-4" /> Add Session
                </button>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- The current session and how far into it we are --}}
                <div class="sa-card p-5 min-w-0" data-ss-current>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Current Session</h3>
                            <p class="sa-card-sub">The one every panel's top bar shows</p>
                        </div>
                        @if ($current)
                            @php [$cw, $ct] = $statusChip[$current->status()]; @endphp
                            <span class="an-tag {{ $ct }}">{{ $cw }}</span>
                        @endif
                    </div>
                    @if ($current)
                        <div class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <p class="sd-big">{{ $current->name }}</p>
                            <p class="text-xs text-gray-400">{{ $current->starts_on->format('d M Y') }} – {{ $current->ends_on->format('d M Y') }}</p>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
                            <span>{{ $daysPct }}% of the session gone</span>
                            <span class="tabular-nums">{{ $num($left) }} {{ $left === 1 ? 'day' : 'days' }} left</span>
                        </div>
                        <div class="an-meter mt-1.5" style="height: 10px"><i style="width: {{ $daysPct }}%; background: #0d9488"></i></div>
                        <dl class="sd-figs mt-4">
                            <div><dt>Schools</dt><dd>{{ $num($curCount['total']) }}</dd></div>
                            <div><dt>New in it</dt><dd class="up">{{ $num($curCount['new']) }}</dd></div>
                            <div><dt>Months</dt><dd>{{ $current->months() }}</dd></div>
                            <div><dt>Days</dt><dd>{{ $num($span) }}</dd></div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-400 py-10 text-center">No session is current yet. Click <b>Make current</b> on one below.</p>
                    @endif
                </div>

                {{-- All the sessions, and the schools in each --}}
                <div class="sa-card p-5 min-w-0" data-ss-all>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Sessions</h3>
                            <p class="sa-card-sub">Schools on SuperLMS by each session's last day</p>
                        </div>
                        <span class="sa-chip">{{ $num($sessions->count()) }} {{ $sessions->count() === 1 ? 'session' : 'sessions' }}</span>
                    </div>
                    <dl class="sd-figs mt-4" style="--c: 3">
                        <div><dt>Running</dt><dd class="up">{{ $num(($byStatus['running'] ?? collect())->count()) }}</dd></div>
                        <div><dt>Upcoming</dt><dd>{{ $num(($byStatus['upcoming'] ?? collect())->count()) }}</dd></div>
                        <div><dt>Completed</dt><dd>{{ $num(($byStatus['completed'] ?? collect())->count()) }}</dd></div>
                    </dl>
                    <div class="mt-5">
                        @include('livewire.super-admin.partials.an-bars', ['rows' => $perSession, 'unit' => 'schools', 'empty' => 'No sessions yet'])
                    </div>
                </div>
            </div>

            {{-- ── Every session ── --}}
            <div class="sa-card overflow-hidden" data-ss-list>
                <div class="px-5 pt-5 pb-4 flex flex-wrap items-start justify-between gap-2 border-b border-gray-100">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">All Sessions</h3>
                        <p class="sa-card-sub">Make one current, edit or delete it — › lists its schools</p>
                    </div>
                </div>

                @if ($sessions->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <span class="ss-ic mx-auto mb-3"><x-icon name="calendar-days" /></span>
                        <p class="text-sm text-gray-500">No sessions yet. Click <strong>Add Session</strong> to add the first one.</p>
                    </div>
                @else
                    <div class="hidden md:block overflow-x-auto">
                        <table class="sk-table min-w-[860px]">
                            <thead>
                                <tr>
                                    <th class="w-12">#</th>
                                    <th>Session</th>
                                    <th>Duration</th>
                                    <th>Schools</th>
                                    <th>Status</th>
                                    <th>Current Session</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sessions as $i => $session)
                                    @php [$statusWord, $statusTone] = $statusChip[$session->status()]; $c = $counts[$session->id]; @endphp
                                    <tr wire:key="session-{{ $session->id }}" class="{{ $session->is_current ? 'is-current' : '' }}">
                                        <td class="text-gray-400 tabular-nums">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="ss-ic {{ $session->is_current ? 'on' : '' }}"><x-icon name="calendar-days" /></span>
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-gray-900 truncate">{{ $session->name }}</p>
                                                    <p class="text-xs text-gray-400">{{ $session->months() }} {{ $session->months() === 1 ? 'month' : 'months' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-gray-600 whitespace-nowrap">{{ $session->starts_on->format('d M Y') }} – {{ $session->ends_on->format('d M Y') }}</td>
                                        <td class="whitespace-nowrap">
                                            <span class="font-semibold text-gray-900 tabular-nums">{{ $num($c['total']) }}</span>
                                            @if ($c['new'] > 0)
                                                <span class="an-tag green ml-1" title="Registered during this session">+{{ $num($c['new']) }} new</span>
                                            @endif
                                        </td>
                                        <td><span class="an-tag {{ $statusTone }}">{{ $statusWord }}</span></td>
                                        <td>
                                            @if ($session->is_current)
                                                <span class="ss-cur">{!! $tickIcon !!} Current</span>
                                            @else
                                                <button type="button" wire:click="askMakeCurrent({{ $session->id }})" wire:loading.attr="disabled" wire:target="askMakeCurrent({{ $session->id }})" class="ss-mc">
                                                    {!! $lockIcon !!}
                                                    <span wire:loading.remove wire:target="askMakeCurrent({{ $session->id }})">Make current</span>
                                                    <span wire:loading wire:target="askMakeCurrent({{ $session->id }})">Sending codes…</span>
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" wire:click="edit({{ $session->id }})" title="Edit" aria-label="Edit" class="sk-ic"><x-icon name="pencil-square" /></button>
                                                <button type="button" wire:click="confirmDelete({{ $session->id }})" title="{{ $session->is_current ? 'The current session can’t be deleted' : 'Delete' }}" aria-label="Delete"
                                                    class="sk-ic rose" @disabled($session->is_current)><x-icon name="trash" /></button>
                                                <button type="button" wire:click="openSchools({{ $session->id }})" title="Schools in {{ $session->name }}" aria-label="Schools in {{ $session->name }}" class="sk-go ml-1">{!! $chevron !!}</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Phones: a row each --}}
                    <div class="md:hidden divide-y divide-gray-100">
                        @foreach ($sessions as $session)
                            @php [$statusWord, $statusTone] = $statusChip[$session->status()]; $c = $counts[$session->id]; @endphp
                            <div class="px-4 py-4" wire:key="session-m-{{ $session->id }}">
                                <div class="flex items-center gap-3">
                                    <span class="ss-ic {{ $session->is_current ? 'on' : '' }}"><x-icon name="calendar-days" /></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 truncate">{{ $session->name }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $session->starts_on->format('d M Y') }} – {{ $session->ends_on->format('d M Y') }}</p>
                                    </div>
                                    <button type="button" wire:click="openSchools({{ $session->id }})" aria-label="Schools in {{ $session->name }}" class="sk-go">{!! $chevron !!}</button>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="an-tag {{ $statusTone }}">{{ $statusWord }}</span>
                                    <span class="an-tag">{{ $num($c['total']) }} schools</span>
                                    @if ($session->is_current)
                                        <span class="ss-cur">{!! $tickIcon !!} Current</span>
                                    @else
                                        <button type="button" wire:click="askMakeCurrent({{ $session->id }})" class="ss-mc">{!! $lockIcon !!} Make current</button>
                                    @endif
                                    <span class="ml-auto flex items-center gap-1">
                                        <button type="button" wire:click="edit({{ $session->id }})" aria-label="Edit" class="sk-ic"><x-icon name="pencil-square" /></button>
                                        @unless ($session->is_current)
                                            <button type="button" wire:click="confirmDelete({{ $session->id }})" aria-label="Delete" class="sk-ic rose"><x-icon name="trash" /></button>
                                        @endunless
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <p class="text-xs text-gray-400 flex items-start gap-1.5">
                <span class="w-3.5 h-3.5 mt-px flex-shrink-0">{!! $lockIcon !!}</span>
                The current session is the one every panel's top bar shows. Making a session current asks for two codes, each verified on its own — one sent to the Super Admin's registered email, one on WhatsApp to the mobile. The › on a session lists its schools.
            </p>
        </div>
    @elseif ($school)
        {{-- ══════════════════════ ONE SCHOOL'S DATA IN THE SESSION ══════════════════════ --}}
        @include('livewire.super-admin.partials.session-school-data', ['open' => $open, 'school' => $school, 'statusChip' => $statusChip])
    @else
        {{-- ══════════════════════ ONE SESSION'S SCHOOLS ══════════════════════ --}}
        @php
            $filtered = $schoolSearch !== '' || $schoolJoined !== '' || $schoolStatus !== '' || $schoolSort !== 'name';
            [$openWord, $openTone] = $statusChip[$open->status()];
            $reloads = 'schoolSearch,schoolJoined,schoolStatus,schoolSort,gotoPage,nextPage,previousPage,clearSchoolFilters';
            $isNew = fn ($school) => $school->created_at && $school->created_at->between($start, $end);
        @endphp

        <div class="sk-head">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" wire:click="backToSessions" title="Back to sessions" aria-label="Back to sessions" class="sk-back">{!! $backIcon !!}</button>
                <div class="sk-title">
                    <h1 class="truncate">Schools · {{ $open->name }}</h1>
                    <p class="hidden md:block">{{ $open->starts_on->format('d M Y') }} – {{ $open->ends_on->format('d M Y') }}</p>
                </div>
            </div>
            <div class="sk-right">
                <span class="an-tag {{ $openTone }}">{{ $openWord }}</span>
                @if ($open->is_current)
                    <span class="ss-cur">{!! $tickIcon !!} Current</span>
                @endif
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5">
            <div class="sa-card p-5" data-ss-figures>
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">{{ $open->name }} at a Glance</h3>
                        <p class="sa-card-sub">Schools on SuperLMS by the session's last day, with their students and teachers by then</p>
                    </div>
                    <span class="sa-chip">{{ $pctOf($schoolTotals['active'], $schoolTotals['schools']) }}% active</span>
                </div>
                <dl class="sd-figs mt-4" style="--c: 5">
                    <div><dt>Schools</dt><dd>{{ $num($schoolTotals['schools']) }}</dd></div>
                    <div><dt>New this session</dt><dd class="up">{{ $num($schoolTotals['new']) }}</dd></div>
                    <div><dt>Active</dt><dd>{{ $num($schoolTotals['active']) }}</dd></div>
                    <div><dt>Students</dt><dd>{{ $num($schoolTotals['students']) }}</dd></div>
                    <div><dt>Teachers</dt><dd>{{ $num($schoolTotals['teachers']) }}</dd></div>
                    <div class="sm:hidden"><dt>Inactive</dt><dd>{{ $num($schoolTotals['schools'] - $schoolTotals['active']) }}</dd></div>
                </dl>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card p-5 min-w-0" data-ss-top>
                    <div class="mb-4">
                        <h3 class="sa-card-title">Biggest Schools</h3>
                        <p class="sa-card-sub">By students on the roll by the session's end</p>
                    </div>
                    @include('livewire.super-admin.partials.an-bars', ['rows' => $schoolTotals['top'], 'unit' => 'students', 'empty' => 'No students in this session'])
                </div>
                <div class="sa-card p-5 min-w-0" data-ss-joined>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Schools Registered</h3>
                            <p class="sa-card-sub">New schools in each month of {{ $open->name }}</p>
                        </div>
                        <span class="sa-chip">{{ $num($schoolTotals['new']) }} new</span>
                    </div>
                    @include('livewire.super-admin.partials.month-bars', [
                        'values' => array_column($schoolTotals['byMonth'], 'count'), 'labels' => array_column($schoolTotals['byMonth'], 'label'),
                        'height' => 140, 'empty' => 'No school registered during ' . $open->name,
                    ])
                </div>
            </div>

            {{-- ── The schools: filters, then the list; › opens a school's data for this session ── --}}
            <div class="sa-card overflow-hidden" data-ss-schools>
                <div class="px-5 pt-5 pb-1">
                    <h3 class="sa-card-title">Schools in {{ $open->name }}</h3>
                    <p class="sa-card-sub">› opens a school's data for this session</p>
                </div>
                <div class="sk-filter" data-filter-bar>
                    <div class="sk-search">
                        <x-icon name="magnifying-glass" />
                        <input wire:model.live.debounce.300ms="schoolSearch" type="text" placeholder="Search name, email, code…" aria-label="Search schools">
                    </div>
                    <select wire:model.live="schoolJoined" class="sk-select" aria-label="Registered">
                        <option value="">All registered</option>
                        <option value="new">Registered this session</option>
                        <option value="earlier">Registered before it</option>
                    </select>
                    <select wire:model.live="schoolStatus" class="sk-select" aria-label="Status">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select wire:model.live="schoolSort" class="sk-select" aria-label="Sort">
                        <option value="name">Name (A–Z)</option>
                        <option value="newest">Newest registered</option>
                        <option value="students">Most students</option>
                    </select>
                    @if ($filtered)
                        <button type="button" wire:click="clearSchoolFilters" class="sk-pill" style="height: 36px; color: #be123c; border-color: #fecdd3">
                            <x-icon name="x-mark" class="w-3.5 h-3.5" /> Clear
                        </button>
                    @endif
                </div>

                <div class="hidden md:block overflow-x-auto" wire:loading.class="opacity-60" wire:target="{{ $reloads }}">
                    <table class="sk-table min-w-[900px]">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th>School</th>
                                <th>Mobile</th>
                                <th>Board</th>
                                <th>Registered</th>
                                <th class="num">Students</th>
                                <th class="num">Teachers</th>
                                <th class="text-right">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schools as $index => $school)
                                @php $board = \App\Support\BoardName::short($school->education_board); $active = (bool) $school->status; @endphp
                                <tr wire:key="ses-sch-row-{{ $school->id }}">
                                    <td class="text-gray-400 tabular-nums">{{ $schools->firstItem() + $index }}</td>
                                    <td title="{{ $school->name }}{{ $school->email ? ' — ' . $school->email : '' }}">
                                        <div class="flex items-center gap-3 min-w-0 max-w-[340px]">
                                            <span class="sd-logo sm">
                                                @if ($school->logo)<img src="{{ $school->logo }}" loading="lazy" alt="">@else{{ strtoupper(mb_substr($school->name, 0, 1)) }}@endif
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate">{{ $school->name }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $school->email ?: '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tabular-nums whitespace-nowrap">{{ $school->mobile_number ?: '—' }}</td>
                                    <td class="truncate max-w-[140px]" title="{{ $board }}">{{ $board ?: '—' }}</td>
                                    <td class="whitespace-nowrap">
                                        <span class="text-gray-600">{{ $school->created_at?->format('d M Y') ?? '—' }}</span>
                                        @if ($isNew($school))
                                            <span class="an-tag green ml-1" title="Registered during {{ $open->name }}">New</span>
                                        @endif
                                    </td>
                                    <td class="num">{{ $num($school->total_students) }}</td>
                                    <td class="num">{{ $num($school->total_teachers) }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2.5">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $active ? 'bg-emerald-500' : 'bg-rose-500' }}"
                                                title="{{ $active ? 'Active' : 'Inactive' }}" role="img" aria-label="{{ $active ? 'Active' : 'Inactive' }}"></span>
                                            <button type="button" wire:click="openSchoolData({{ $school->id }})"
                                                title="{{ $school->name }} — its data in {{ $open->name }}" aria-label="{{ $school->name }} — its data in {{ $open->name }}" class="sk-go">{!! $chevron !!}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 56px 20px">
                                        <p class="text-gray-500 text-sm">{{ $filtered ? 'No school matches these filters.' : 'No school was on SuperLMS by the end of ' . $open->name . '.' }}</p>
                                        @if ($filtered)
                                            <button type="button" wire:click="clearSchoolFilters" class="mt-2 text-xs text-gray-900 underline underline-offset-2 font-medium">Clear filters</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Phones: a row each --}}
                <div class="md:hidden divide-y divide-gray-100">
                    @forelse ($schools as $index => $school)
                        @php $active = (bool) $school->status; @endphp
                        <div class="px-4 py-4 flex items-center gap-3" wire:key="ses-sch-m-{{ $school->id }}">
                            <span class="sd-logo sm">
                                @if ($school->logo)<img src="{{ $school->logo }}" alt="">@else{{ strtoupper(mb_substr($school->name, 0, 1)) }}@endif
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $school->name }}</p>
                                <p class="text-xs text-gray-400 truncate">
                                    {{ $num($school->total_students) }} students · {{ $num($school->total_teachers) }} teachers
                                    @if ($isNew($school)) · <span class="text-emerald-700 font-medium">New</span>@endif
                                </p>
                            </div>
                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            <button type="button" wire:click="openSchoolData({{ $school->id }})" aria-label="View {{ $open->name }} data" class="sk-go">{!! $chevron !!}</button>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-gray-500 text-sm">{{ $filtered ? 'No school matches these filters.' : 'No school was on SuperLMS by the end of ' . $open->name . '.' }}</p>
                    @endforelse
                </div>

                @include('livewire.super-admin.partials.list-pagination', ['paginator' => $schools])
            </div>
        </div>
    @endif

    {{-- ══════════ ADD / EDIT SLIDE-IN PANEL ══════════ --}}
    @if ($showPanel)
        @php $editingCurrent = $editId && $sessions->firstWhere('id', $editId)?->is_current; @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>

            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col" style="border-radius: 22px 0 0 22px">
                <div class="flex items-center justify-between gap-3 px-6 py-5 border-b border-gray-100 flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="ss-ic on"><x-icon name="calendar-days" /></span>
                        <div class="min-w-0">
                            <h2 class="sk-display text-lg font-extrabold">{{ $editId ? 'Edit Session' : 'Add Session' }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">The school year's first and last day — the name follows the dates</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closePanel" aria-label="Close" class="sk-back">
                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">First day <span class="text-rose-500">*</span></label>
                                <input type="date" wire:model.live="startsOn" class="ss-field">
                                @error('startsOn')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Last day <span class="text-rose-500">*</span></label>
                                <input type="date" wire:model.live="endsOn" class="ss-field">
                                @error('endsOn')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Session name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model.live.debounce.400ms="name" maxlength="20" placeholder="e.g. 2026–27" class="ss-field">
                            @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        @if ($editingCurrent)
                            <div class="ss-otp done flex items-center gap-3">
                                <span class="ss-otp-ic">{!! $tickIcon !!}</span>
                                <span class="text-sm font-medium text-gray-800">This is the current session.</span>
                            </div>
                        @else
                            <label class="ss-otp flex items-start gap-3 cursor-pointer {{ $makeCurrent ? 'done' : '' }}">
                                <input type="checkbox" wire:model.live="makeCurrent" class="mt-1 rounded border-gray-300" style="accent-color: #0d9488; color: #0d9488">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-800">Make this the current session</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">After saving you'll verify two codes, each on its own — one sent to the Super Admin's email, one on WhatsApp to the mobile.</span>
                                </span>
                            </label>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePanel" type="button" class="sk-pill" style="height: 38px; padding: 0 18px; font-size: 13.5px">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save" class="sk-cta" style="height: 38px">
                        <span wire:loading.remove wire:target="save">{{ $editId ? 'Update Session' : 'Save Session' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ MAKE CURRENT — two codes, each verified on its own ══════════ --}}
    @if ($switchId && $switching)
        @php
            $codes = [
                ['email', 'emailCode', 'Email code', 'Sent to ' . ($switchTo['email'] ?: 'the registered email'), 'envelope', 'Email'],
                ['whatsapp', 'whatsappCode', 'Mobile code', 'Sent on WhatsApp to ' . ($switchTo['whatsapp'] ?: 'the registered mobile'), 'device-phone-mobile', 'Mobile'],
            ];
            $done    = count(array_filter($verified));
            $nextOne = !($verified['email'] ?? false) ? 'email' : 'whatsapp';
        @endphp
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="make-current-title" data-make-current>
            <div class="absolute inset-0 bg-black/40" wire:click="closeSwitch"></div>
            <div class="relative bg-white shadow-xl w-full max-w-md max-h-full overflow-y-auto" style="border-radius: 24px">
                <div class="px-6 pt-6 pb-5 text-center border-b border-gray-100">
                    <span class="ss-otp-ic mx-auto mb-3" style="width: 48px; height: 48px; border-radius: 16px"><x-icon name="shield-check" class="w-6 h-6" /></span>
                    <h3 id="make-current-title" class="sk-display text-lg font-extrabold">Make {{ $switching->name }} the current session</h3>
                    <p class="text-sm text-gray-500 mt-1">Verify the email code and the mobile code — each on its own. {{ $switching->name }} becomes current once both are verified.</p>
                    @if ($current)
                        <p class="text-xs text-gray-400 mt-2">Now current: <strong class="text-gray-600">{{ $current->name }}</strong> → after this: <strong style="color: var(--sk-teal-700)">{{ $switching->name }}</strong></p>
                    @endif
                    <div class="mt-4 flex items-center justify-center gap-2" data-otp-steps>
                        @foreach ($codes as [$channel, , , , , $short])
                            <span class="ss-step {{ ($verified[$channel] ?? false) ? 'done' : '' }}">
                                @if ($verified[$channel] ?? false){!! $tickIcon !!}@else<span class="tabular-nums">{{ $loop->iteration }}</span>@endif
                                {{ $short }} {{ ($verified[$channel] ?? false) ? 'verified' : 'code' }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">
                    @foreach ($codes as [$channel, $field, $label, $where, $icon])
                        @php $isDone = (bool) ($verified[$channel] ?? false); $left = max(0, ($resendAt[$channel] ?? 0) - now()->timestamp); @endphp
                        <div class="ss-otp {{ $isDone ? 'done' : '' }}" wire:key="otp-{{ $channel }}" data-otp="{{ $channel }}">
                            <div class="flex items-center gap-3">
                                <span class="ss-otp-ic">@if ($isDone){!! $tickIcon !!}@else<x-icon name="{{ $icon }}" />@endif</span>
                                <div class="min-w-0 flex-1">
                                    <label for="code-{{ $channel }}" class="block text-sm font-semibold text-gray-800">{{ $label }}</label>
                                    <p class="text-xs text-gray-400 truncate">{{ $where }}</p>
                                </div>
                                @if ($isDone)
                                    <span class="an-tag teal">Verified</span>
                                @else
                                    <div class="text-xs flex-shrink-0" wire:key="resend-{{ $channel }}-{{ $resendAt[$channel] ?? 0 }}"
                                        x-data="{ left: {{ $left }} }" x-init="const t = setInterval(() => { left > 0 ? left-- : clearInterval(t) }, 1000)">
                                        <span x-show="left > 0" class="text-gray-400 tabular-nums" x-text="'Resend in ' + left + 's'"></span>
                                        <button type="button" x-show="left <= 0" x-cloak wire:click="resendCode('{{ $channel }}')" wire:loading.attr="disabled" wire:target="resendCode('{{ $channel }}')"
                                            class="font-semibold disabled:opacity-50" style="color: var(--sk-teal-700)">Resend</button>
                                    </div>
                                @endif
                            </div>
                            @unless ($isDone)
                                <form wire:submit="verifyCode('{{ $channel }}')" class="mt-3 flex items-center gap-2">
                                    <input id="code-{{ $channel }}" type="text" wire:model="{{ $field }}" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                                        autocomplete="one-time-code" placeholder="••••••" @if ($channel === $nextOne) autofocus @endif
                                        class="ss-code {{ $errors->has($field) ? 'bad' : '' }}">
                                    <button type="submit" class="sk-cta" style="height: 44px; padding: 0 18px" wire:loading.attr="disabled" wire:target="verifyCode('{{ $channel }}')">
                                        <span wire:loading.remove wire:target="verifyCode('{{ $channel }}')">Verify</span>
                                        <span wire:loading wire:target="verifyCode('{{ $channel }}')">Checking…</span>
                                    </button>
                                </form>
                                @error($field)<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                            @endunless
                        </div>
                    @endforeach

                    @error('request')
                        <div class="rounded-2xl bg-amber-50 border border-amber-200 px-3.5 py-2.5 text-xs text-amber-800">{{ $message }}</div>
                    @enderror
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-2">
                    <p class="text-xs text-gray-400">{{ $done }} of 2 verified</p>
                    <button type="button" wire:click="closeSwitch" class="sk-pill" style="height: 38px; padding: 0 18px; font-size: 13.5px">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($deleteId)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="cancelDelete"></div>
            <div class="relative bg-white shadow-xl w-full max-w-sm p-6 text-center" style="border-radius: 24px">
                <div class="mx-auto w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 19h14.14A2 2 0 0021 17.05L13.86 4.9a2 2 0 00-3.46 0L3.07 17.05A2 2 0 004.93 19z"/></svg>
                </div>
                <h3 class="sk-display text-lg font-extrabold">Delete this session?</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $sessions->firstWhere('id', $deleteId)?->name }} will be removed. This can't be undone.</p>
                <div class="flex items-center justify-center gap-2 mt-5">
                    <button type="button" wire:click="cancelDelete" class="sk-pill" style="height: 38px; padding: 0 18px; font-size: 13.5px">Cancel</button>
                    <button type="button" wire:click="delete" class="inline-flex items-center h-[38px] px-5 rounded-full text-sm font-semibold text-white" style="background: #e11d48">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
