<div class="sa-skin sa-home min-h-screen">
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')

    {{-- School Data in the Home's analytics look (2026-11-21): the School
         Detail, the analytics of everything the school keeps, two cards to a
         row, then every functionality with its PDF, two to a row. No lists
         of records on the page (the user had them taken off). --}}
    <style>
        .sd-figs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px; background: #eef1f0; border: 1px solid #eef1f0; border-radius: 16px; overflow: hidden; }
        @media (min-width: 640px) { .sd-figs { grid-template-columns: repeat(var(--c, 4), minmax(0, 1fr)); } }
        .sd-figs > div { background: #fff; padding: 11px 14px; min-width: 0; }
        .sd-figs dt { font-size: 11.5px; color: #6b7c76; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sd-figs dd { margin-top: 2px; font-size: 15px; font-weight: 600; color: var(--sk-ink); font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sd-figs dd.up { color: #047857; }
        .sd-figs dd.down { color: #be123c; }
        .sd-figs dd.words { font-size: 13.5px; font-weight: 500; }

        .sd-big { font-family: var(--sk-display); font-size: 30px; font-weight: 800; letter-spacing: -.045em; color: var(--sk-ink); line-height: 1; font-variant-numeric: tabular-nums; }
        .sd-foot { margin-top: 14px; padding-top: 12px; border-top: 1px solid #f0f3f2; font-size: 12.5px; color: #6b7c76; line-height: 1.5; }
        .sd-foot b { color: var(--sk-ink); font-weight: 600; font-variant-numeric: tabular-nums; }

        .sd-bars { display: flex; align-items: flex-end; gap: 6px; padding-top: 22px; }
        .sd-bar { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: 7px; }
        .sd-bar-t { position: relative; width: 100%; max-width: 30px; height: var(--h); display: flex; align-items: flex-end; border-radius: 8px; background: #f3f6f5; }
        .sd-bar-t > i { display: block; width: 100%; border-radius: 8px; background: #99e0d3; transition: background .2s; }
        .sd-bar.is-now .sd-bar-t > i { background: #0d9488; }
        .sd-bar:hover .sd-bar-t > i { background: #0f766e; }
        .sd-bar-tip { position: absolute; left: 50%; bottom: calc(var(--p) + 5px); transform: translate(-50%, 4px); padding: 2px 7px; border-radius: 999px; background: #0c2620; color: #fff; font-size: 10.5px; font-weight: 600; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .15s, transform .15s; z-index: 2; }
        .sd-bar:hover .sd-bar-tip { opacity: 1; transform: translate(-50%, 0); }
        .sd-bar > b { font-size: 10.5px; font-weight: 500; color: #95a39e; white-space: nowrap; overflow: hidden; max-width: 100%; text-overflow: clip; }
        @media (max-width: 479px) { .sd-bars.sd-12 .sd-bar:nth-child(even) > b { visibility: hidden; } }

        .sd-logo { width: 64px; height: 64px; border-radius: 18px; flex: none; display: grid; place-items: center; overflow: hidden; background: var(--sk-teal-50); color: var(--sk-teal-700); font-family: var(--sk-display); font-size: 24px; font-weight: 800; border: 1px solid #eef1f0; }
        .sd-logo img { width: 100%; height: 100%; object-fit: cover; }

        .sd-fn-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 10px; padding: 16px 20px 20px; }
        @media (min-width: 768px) { .sd-fn-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .sd-fn { display: flex; align-items: center; gap: 12px; min-width: 0; padding: 12px 12px 12px 14px; border: 1px solid #eef1f0; border-radius: 16px; background: #fff; transition: border-color .2s, box-shadow .25s, background .2s; }
        .sd-fn:hover { border-color: #b1e6db; background: #fbfefd; box-shadow: 0 14px 28px -22px rgba(12,38,32,.45); }
        .sd-fn-n { width: 34px; height: 34px; border-radius: 11px; flex: none; display: grid; place-items: center; font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums; background: #f4f6f5; color: #8b9893; }
        .sd-fn.has .sd-fn-n { background: var(--sk-teal-50); color: var(--sk-teal-700); }
        .sd-fn-t { display: flex; align-items: center; gap: 8px; min-width: 0; font-size: 14px; font-weight: 600; color: var(--sk-ink); }
        .sd-fn-t > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sd-fn-s { margin-top: 2px; font-size: 12px; color: #8b9893; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sd-pdf { display: inline-flex; align-items: center; gap: 6px; flex: none; height: 34px; padding: 0 14px; border-radius: 999px; border: 1px solid #e3e8e6; background: #fff; color: #44544f; font-size: 12.5px; font-weight: 600; white-space: nowrap; transition: background .2s, border-color .2s, color .2s, box-shadow .25s; }
        .sd-pdf:hover { border-color: var(--sk-teal); color: var(--sk-teal-700); background: var(--sk-teal-50); }
        .sd-pdf.on { background: var(--sk-teal); border-color: var(--sk-teal); color: #fff; }
        .sd-pdf.on:hover { background: var(--sk-teal-700); border-color: var(--sk-teal-700); box-shadow: 0 10px 20px -12px rgba(13,148,136,.8); }
        .sd-pdf:disabled { opacity: .6; cursor: wait; }
        .sd-pdf svg { width: 14px; height: 14px; }
    </style>

    @php
        $inr    = fn ($v) => '₹' . number_format((float) $v, 0);
        $short  = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $trend  = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));
        $pctOf  = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100, 1) : 0;
        $num    = fn ($v) => number_format((float) $v);
        $monthL = $an ? array_map(fn ($l) => explode(' ', (string) $l)[0], $an['months']) : [];
    @endphp

    {{-- ══════════ HEADER — the school, Send to Mail, Excel ══════════ --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>School Data</h1>
            <p class="hidden sm:block">Analytics and functionalities of one school</p>
        </div>

        <div class="sk-right">
            @unless ($locked)
                <select wire:model.live="school" aria-label="School" class="sk-select" style="height: 36px; max-width: 18rem; font-size: 13.5px">
                    @foreach ($schools as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            @endunless
            @if ($org)
                {{-- Every functionality's PDF to the school's registered email. --}}
                @php $mailTo = filter_var(trim((string) $org->email), FILTER_VALIDATE_EMAIL) ? trim($org->email) : null; @endphp
                <button wire:click="sendToMail" wire:loading.attr="disabled" wire:target="sendToMail" type="button"
                    @if ($mailTo) wire:confirm="Send every PDF of {{ $org->name }} to {{ $mailTo }}?" @endif
                    title="{{ $mailTo ? 'Send all PDFs to ' . $mailTo : 'This school has no registered email' }}"
                    @disabled(!$mailTo || ($mailStatus['state'] ?? null) === 'sending')
                    class="sk-pill disabled:opacity-50 disabled:cursor-not-allowed" style="height: 36px; padding: 0 14px; font-size: 13.5px"
                    data-send-to-mail>
                    <svg class="w-4 h-4" wire:loading.remove wire:target="sendToMail" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <svg class="w-4 h-4 animate-spin" wire:loading wire:target="sendToMail" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    Send to Mail
                </button>

                {{-- The students, teachers, staff, classes or fees as an Excel sheet. --}}
                <div class="relative" x-data="{ open: false }" x-on:click.away="open = false" x-on:keydown.escape="open = false">
                    <button type="button" class="sk-cta" x-on:click="open = !open" :aria-expanded="open" data-excel-menu>
                        <svg class="w-4 h-4" wire:loading.remove wire:target="export" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <svg class="w-4 h-4 animate-spin" wire:loading wire:target="export" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Export Excel
                        <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="sk-menu" x-show="open" x-transition style="display: none; left: auto; right: 0; width: 220px">
                        <p class="sk-menu-h">Download as Excel</p>
                        @foreach (\App\Livewire\SuperAdmin\SchoolData::EXPORTS as $key => $label)
                            <a href="#" role="button" wire:click.prevent="export('{{ $key }}')" x-on:click="open = false">
                                <x-icon name="table-cells" class="w-4 h-4 text-gray-400" />{{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">
        @if (!$org)
            <div class="sa-card px-6 py-16 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center">
                    <x-icon name="circle-stack" class="w-5 h-5" />
                </div>
                <p class="text-sm text-gray-500">No schools yet. Add one on <a href="{{ route('super-admin.schools') }}" class="font-medium text-violet-600 hover:text-violet-800">Schools</a>.</p>
            </div>
        @else
            @php
                $st  = $an['students'];
                $tc  = $an['teachers'];
                $sf  = $an['staff'];
                $cl  = $an['classes'];
                $at  = $an['attendance'];
                $fee = $an['fee'];
                $use = $an['usage'];
                $feePct = $pctOf($fee['collected'], $fee['totalFee']);
            @endphp

            {{-- ══════════ School Detail ══════════ --}}
            <div class="sa-card p-5 sm:p-6" data-school-detail>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">School Detail</h3>
                        <p class="sa-card-sub">Who the school is and how to reach it</p>
                    </div>
                    <span class="an-tag {{ $org->status ? 'green' : 'rose' }}">{{ $org->status ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="mt-5 flex items-center gap-4 min-w-0">
                    <span class="sd-logo">
                        @if ($org->logo)
                            <img src="{{ $org->logo }}" alt="">
                        @else
                            {{ strtoupper(mb_substr($org->name, 0, 1)) }}
                        @endif
                    </span>
                    <div class="min-w-0">
                        <h2 class="sk-display text-lg sm:text-xl font-extrabold leading-tight break-words">{{ $org->name }}</h2>
                        @if ($org->second_name)
                            <p class="text-xs text-gray-500 mt-0.5 break-words">Also {{ $org->second_name }}</p>
                        @endif
                        <p class="text-sm text-gray-500 mt-1 break-words">
                            {{ collect([$org->address, $org->state])->map(fn ($v) => trim((string) $v))->filter()->join(', ') ?: 'No address added' }}
                        </p>
                    </div>
                </div>
                <dl class="sd-figs mt-5">
                    @foreach ([
                        ['Phone', $org->mobile_number],
                        ['Email', $org->email],
                        ['School Code', $org->school_code],
                        ['Board', $org->education_board],
                        ['Medium', $org->medium],
                        ['Affiliation No.', $org->affiliation_no],
                        ['UDISE No.', $org->udise_number],
                        ['Joined', $org->created_at?->format('d M Y')],
                    ] as [$label, $value])
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd class="words" title="{{ $value }}">{{ trim((string) $value) !== '' ? $value : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- ══════════ Analytics — KPIs ══════════ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Students', 'value' => $num($st['total']), 'icon' => 'users', 'color' => 'teal',
                    'series' => $st['added'], 'trend' => $trend($st['added']),
                    'sub' => $num($st['active']) . ' active · ' . $num($st['thisMonth']) . ' added this month',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Teachers', 'value' => $num($tc['total']), 'icon' => 'academic-cap', 'color' => 'teal',
                    'series' => $tc['added'], 'trend' => $trend($tc['added']),
                    'sub' => $num($tc['active']) . ' active · ' . $num($sf['total']) . ' other staff',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Fees Collected', 'value' => $short($fee['collected']), 'icon' => 'banknotes', 'color' => 'teal',
                    'series' => $fee['byMonth'], 'trend' => $trend($fee['byMonth']),
                    'sub' => $feePct . '% of ' . $short($fee['totalFee']) . ' this session',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Present Today', 'value' => $at['pct'] !== null ? $at['pct'] . '%' : '—', 'icon' => 'check-badge', 'color' => 'teal',
                    'series' => array_map(fn ($d) => (float) ($d['pct'] ?? 0), $at['week']), 'trend' => null, 'flat' => false,
                    'sub' => $at['marked'] > 0 ? $num($at['present']) . ' of ' . $num($at['marked']) . ' students marked present' : 'Attendance not marked yet today',
                ])
            </div>

            {{-- ══════════ Analytics — a card for everything, two to a row ══════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5" data-school-analytics>

                {{-- Fee --}}
                <div class="sa-card p-5 min-w-0" data-an-fee>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Fee Collection</h3>
                            <p class="sa-card-sub">This session's fee against what has come in</p>
                        </div>
                        <span class="sa-chip">{{ $feePct }}% collected</span>
                    </div>
                    <div class="an-meter mt-4" style="height: 10px"><i style="width: {{ min(100, $feePct) }}%; background: #0d9488"></i></div>
                    <dl class="sd-figs mt-4">
                        <div><dt>Total fee</dt><dd title="{{ $inr($fee['totalFee']) }}">{{ $inr($fee['totalFee']) }}</dd></div>
                        <div><dt>Collected</dt><dd class="up" title="{{ $inr($fee['collected']) }}">{{ $inr($fee['collected']) }}</dd></div>
                        <div><dt>Pending</dt><dd class="{{ $fee['remaining'] > 0 ? 'down' : '' }}" title="{{ $inr($fee['remaining']) }}">{{ $inr($fee['remaining']) }}</dd></div>
                        <div><dt>This month</dt><dd title="{{ $inr($fee['thisMonth']) }}">{{ $inr($fee['thisMonth']) }}</dd></div>
                    </dl>
                    <p class="mt-5 text-xs font-medium text-gray-500">Collected each month · last 12 months</p>
                    @include('livewire.super-admin.partials.month-bars', [
                        'values' => $fee['byMonth'], 'labels' => $monthL, 'format' => $short, 'height' => 120,
                        'empty' => 'No fee collected in the last 12 months',
                    ])
                    <p class="sd-foot">
                        Academic <b>{{ $inr($fee['academicFee']) }}</b> · Bus <b>{{ $inr($fee['transportFee']) }}</b> · Last year dues <b>{{ $inr($fee['lastYearDues']) }}</b>
                    </p>
                </div>

                {{-- Attendance --}}
                @php $notMarked = max(0, $st['active'] - $at['marked']); @endphp
                <div class="sa-card p-5 min-w-0" data-an-attendance>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Student Attendance</h3>
                            <p class="sa-card-sub">Today and the last 7 days</p>
                        </div>
                        <span class="sa-chip">{{ $at['monthPct'] !== null ? $at['monthPct'] . '% this month' : 'Not marked this month' }}</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <p class="sd-big">{{ $at['pct'] !== null ? $at['pct'] . '%' : '—' }}</p>
                        <p class="text-xs text-gray-400">present today</p>
                    </div>
                    <dl class="sd-figs mt-4">
                        <div><dt>Present</dt><dd class="up">{{ $num($at['present']) }}</dd></div>
                        <div><dt>Absent</dt><dd class="{{ $at['absent'] > 0 ? 'down' : '' }}">{{ $num($at['absent']) }}</dd></div>
                        <div><dt>Not marked</dt><dd>{{ $num($notMarked) }}</dd></div>
                        <div><dt>Days this month</dt><dd>{{ $num($at['monthDays']) }}</dd></div>
                    </dl>
                    <p class="mt-5 text-xs font-medium text-gray-500">Present each day · last 7 days</p>
                    @include('livewire.super-admin.partials.month-bars', [
                        'values' => array_column($at['week'], 'pct'), 'labels' => array_column($at['week'], 'label'),
                        'format' => fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.') . '%', 'height' => 120, 'max' => 100,
                        'empty' => 'No attendance marked in the last 7 days',
                    ])
                </div>

                {{-- Students --}}
                <div class="sa-card p-5 min-w-0" data-an-students>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Students</h3>
                            <p class="sa-card-sub">Who is enrolled, and admissions month by month</p>
                        </div>
                        <span class="sa-chip">{{ $num($st['thisMonth']) }} new this month</span>
                    </div>
                    <dl class="sd-figs mt-4" style="--c: 3">
                        <div><dt>Total</dt><dd>{{ $num($st['total']) }}</dd></div>
                        <div><dt>Active</dt><dd class="up">{{ $num($st['active']) }}</dd></div>
                        <div><dt>Inactive</dt><dd class="{{ $st['inactive'] > 0 ? 'down' : '' }}">{{ $num($st['inactive']) }}</dd></div>
                        <div><dt>Boys</dt><dd>{{ $num($st['boys']) }}</dd></div>
                        <div><dt>Girls</dt><dd>{{ $num($st['girls']) }}</dd></div>
                        <div><dt>Without a class</dt><dd>{{ $num($st['noClass']) }}</dd></div>
                    </dl>
                    <p class="mt-5 text-xs font-medium text-gray-500">Students added each month · last 12 months</p>
                    @include('livewire.super-admin.partials.month-bars', [
                        'values' => $st['added'], 'labels' => $monthL, 'height' => 120,
                        'empty' => 'No students added in the last 12 months',
                    ])
                </div>

                {{-- Classes --}}
                <div class="sa-card p-5 min-w-0 flex flex-col" data-an-classes>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Students by Class</h3>
                            <p class="sa-card-sub">The classes with the most students</p>
                        </div>
                        <span class="sa-chip">{{ $num($cl['classes']) }} {{ $cl['classes'] === 1 ? 'class' : 'classes' }} · {{ $num($cl['sections']) }} {{ $cl['sections'] === 1 ? 'section' : 'sections' }}</span>
                    </div>
                    @include('livewire.super-admin.partials.an-bars', ['rows' => $cl['rows'], 'empty' => 'No students in a class yet'])
                    <p class="sd-foot mt-auto">
                        About <b>{{ $num($cl['average']) }}</b> students a class
                        @if ($cl['more'] > 0) · <b>{{ $num($cl['more']) }}</b> more {{ $cl['more'] === 1 ? 'class' : 'classes' }} not shown @endif
                    </p>
                </div>

                {{-- Teachers --}}
                <div class="sa-card p-5 min-w-0" data-an-teachers>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Teachers</h3>
                            <p class="sa-card-sub">On the rolls, at school today, and who joined when</p>
                        </div>
                        <span class="sa-chip">{{ $tc['presentPct'] !== null ? $tc['presentPct'] . '% present today' : 'Not marked today' }}</span>
                    </div>
                    <dl class="sd-figs mt-4">
                        <div><dt>Total</dt><dd>{{ $num($tc['total']) }}</dd></div>
                        <div><dt>Active</dt><dd class="up">{{ $num($tc['active']) }}</dd></div>
                        <div><dt>Inactive</dt><dd class="{{ $tc['inactive'] > 0 ? 'down' : '' }}">{{ $num($tc['inactive']) }}</dd></div>
                        <div><dt>Present today</dt><dd>{{ $tc['marked'] > 0 ? $num($tc['present']) . ' / ' . $num($tc['marked']) : '—' }}</dd></div>
                    </dl>
                    <p class="mt-5 text-xs font-medium text-gray-500">Teachers added each month · last 12 months</p>
                    @include('livewire.super-admin.partials.month-bars', [
                        'values' => $tc['added'], 'labels' => $monthL, 'height' => 120,
                        'empty' => 'No teachers added in the last 12 months',
                    ])
                </div>

                {{-- Staff --}}
                <div class="sa-card p-5 min-w-0 flex flex-col" data-an-staff>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Staff</h3>
                            <p class="sa-card-sub">Everyone besides teachers, by kind</p>
                        </div>
                        <span class="sa-chip">{{ $inr($sf['bill']) }} a month</span>
                    </div>
                    <dl class="sd-figs mt-4" style="--c: 3">
                        <div><dt>Total</dt><dd>{{ $num($sf['total']) }}</dd></div>
                        <div><dt>Active</dt><dd class="up">{{ $num($sf['active']) }}</dd></div>
                        <div><dt>Salary bill</dt><dd title="{{ $inr($sf['bill']) }}">{{ $short($sf['bill']) }}</dd></div>
                    </dl>
                    <div class="mt-5">
                        @include('livewire.super-admin.partials.an-bars', ['rows' => $sf['byType'], 'empty' => 'No staff besides teachers yet'])
                    </div>
                </div>

                {{-- How fees were paid --}}
                <div class="sa-card p-5 min-w-0 flex flex-col" data-an-modes>
                    <div class="mb-4">
                        <h3 class="sa-card-title">Fee by Payment Mode</h3>
                        <p class="sa-card-sub">This session's payments, by how they were made</p>
                    </div>
                    @if (count($fee['modes']))
                        <div class="flex-1 flex items-center">
                            @include('livewire.super-admin.partials.dash-donut', [
                                'segments' => $fee['modes'],
                                'center' => $short(array_sum(array_column($fee['modes'], 'value'))), 'centerLabel' => 'Paid', 'size' => 168, 'legend' => true,
                                'format' => $short,
                            ])
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-10 text-center">No fee paid this session yet</p>
                    @endif
                </div>

                {{-- How much each functionality is used --}}
                <div class="sa-card p-5 min-w-0 flex flex-col" data-an-usage>
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Functionality Usage</h3>
                            <p class="sa-card-sub">Records the school keeps in each functionality</p>
                        </div>
                        <span class="sa-chip">{{ $use['inUse'] }} of {{ $use['total'] }} in use</span>
                    </div>
                    <div class="an-meter mt-4" style="height: 10px"><i style="width: {{ $pctOf($use['inUse'], $use['total']) }}%; background: #0d9488"></i></div>
                    <div class="mt-5">
                        @include('livewire.super-admin.partials.an-bars', ['rows' => $use['rows'], 'unit' => 'records', 'empty' => 'No records yet'])
                    </div>
                    @if (count($use['unused']))
                        <p class="sd-foot mt-auto">Not used yet: {{ implode(', ', array_slice($use['unused'], 0, 8)) }}{{ count($use['unused']) > 8 ? ' and ' . (count($use['unused']) - 8) . ' more' : '' }}</p>
                    @endif
                </div>
            </div>

            {{-- ══════════ Functionalities — each with its PDF, two to a row ══════════
                 All the school's records there, plainly laid out
                 (App\Support\SchoolPdf). Send to Mail, up top, mails them all. --}}
            @php
                $sections = \App\Support\SchoolPdf\Exporter::SECTIONS;
                $mailAt   = !empty($mailStatus['at']) ? \Carbon\Carbon::parse($mailStatus['at'])->timezone(config('app.timezone'))->format('d M, h:i A') : null;
            @endphp
            <div class="sa-card overflow-hidden" data-school-pdfs
                @if (($mailStatus['state'] ?? null) === 'sending') wire:poll.10s @endif>
                <div class="px-5 pt-5 pb-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 border-b border-gray-100">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Functionalities</h3>
                        <p class="sa-card-sub">Export everything {{ $org->name }} keeps in each one as a PDF</p>
                    </div>
                    @if ($mailStatus)
                        <p class="text-xs sm:text-right sm:max-w-[22rem] {{ ($mailStatus['state'] ?? '') === 'failed' ? 'text-rose-600' : 'text-gray-500' }}" data-mail-status>
                            @switch($mailStatus['state'] ?? '')
                                @case('sending')
                                    <span class="inline-flex items-center gap-1.5"><span class="sa-dot animate-pulse" style="background:#0d9488"></span>Sending {{ $mailStatus['files'] ?? '' }} PDFs to {{ $mailStatus['to'] }}…</span>
                                    @break
                                @case('sent')
                                    Sent {{ $mailStatus['files'] ?? '' }} PDFs to {{ $mailStatus['to'] }} · {{ $mailAt }}
                                    @break
                                @case('failed')
                                    Could not send to {{ $mailStatus['to'] }} ({{ $mailAt }}){{ !empty($mailStatus['error']) ? ': ' . $mailStatus['error'] : '' }}
                                    @break
                            @endswitch
                        </p>
                    @endif
                </div>
                <ul class="sd-fn-grid">
                    @foreach ($sections as $key => [$label, $about])
                        @php
                            $n    = $pdfCounts[$key] ?? 0;
                            // Fees counts payments; its PDF has every student's fee even before one.
                            $unit = $key === 'fees' ? ['payment', 'payments', 'No payments yet'] : ['record', 'records', 'No records yet'];
                            $has  = $n > 0 || ($key === 'fees' && ($pdfCounts['students'] ?? 0) > 0);
                        @endphp
                        <li class="sd-fn {{ $has ? 'has' : '' }}" wire:key="sd-pdf-{{ $key }}">
                            <span class="sd-fn-n">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="sd-fn-t">
                                    <span>{{ $label }}</span>
                                    <span class="an-tag {{ $n > 0 ? 'teal' : '' }} tabular-nums">{{ $n > 0 ? number_format($n) . ' ' . ($n === 1 ? $unit[0] : $unit[1]) : $unit[2] }}</span>
                                </p>
                                <p class="sd-fn-s" title="{{ $about }}">{{ $about }}</p>
                            </div>
                            <button type="button" wire:click="exportPdf('{{ $key }}')" wire:loading.attr="disabled" wire:target="exportPdf('{{ $key }}')"
                                class="sd-pdf {{ $has ? 'on' : '' }}">
                                <svg wire:loading.remove wire:target="exportPdf('{{ $key }}')" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M7 21h10a2 2 0 002-2V8.414a1 1 0 00-.293-.707l-4.414-4.414A1 1 0 0013.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <svg class="animate-spin" wire:loading wire:target="exportPdf('{{ $key }}')" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                Export PDF
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
