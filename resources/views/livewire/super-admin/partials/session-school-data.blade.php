{{-- Super Admin → Session → a session's schools → a school's ›: all its data
     in that session (App\Support\SessionSchoolData), in the Home's analytics
     look (2026-11-21): a chip per section as on Analytics — Overview (the
     school's detail and its analytics for the session, two cards to a row),
     then Students, Teachers, Staff, Classes, Fees and Attendance, each list
     with a search and an Excel export. $open (the session), $school ['org',
     'data', 'summary', 'rows', 'classes', 'attendance'], $statusChip. --}}
@php
    $org     = $school['org'];
    $summary = $school['summary'];
    $rows    = $school['rows'];
    $inr     = fn ($v) => '₹' . number_format((float) $v, 0);
    $short   = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
    $num     = fn ($v) => number_format((float) $v);
    $trend   = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));
    $tabs    = \App\Support\SessionSchoolData::TABS;
    $chips   = ['overview' => ['Overview', 'chart-bar', null]] + [
        'students'   => [$tabs['students'] ?? 'Students', 'users', $summary['students']],
        'teachers'   => [$tabs['teachers'] ?? 'Teachers', 'academic-cap', $summary['teachers']],
        'staff'      => [$tabs['staff'] ?? 'Staff', 'briefcase', $summary['staff']],
        'classes'    => [$tabs['classes'] ?? 'Classes', 'rectangle-group', $summary['classes']],
        'fees'       => [$tabs['fees'] ?? 'Fees', 'banknotes', $summary['payments']],
        'attendance' => [$tabs['attendance'] ?? 'Attendance', 'check-badge', null],
    ];
    $searchHint = [
        'students'   => 'Search name, admission no., roll no., phone…',
        'teachers'   => 'Search name, email, phone, employee ID…',
        'staff'      => 'Search name, designation, mobile…',
        'classes'    => 'Search a class…',
        'fees'       => 'Search receipt no., student, admission no.…',
    ][$dataTab] ?? null;
    $tag = fn (bool $on) => $on ? 'green' : 'rose';
    [$openWord, $openTone] = $statusChip[$open->status()];
    $compare = 'vs the month before';
    $monthL  = array_map(fn ($l) => explode(' ', (string) $l)[0], $summary['months']);
    $isList  = array_key_exists($dataTab, $tabs);
    $backIcon = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>';
@endphp

{{-- ══════════ HEADER — back to the session's schools; the open list as Excel ══════════ --}}
<div class="sk-head">
    <div class="flex items-center gap-3 min-w-0">
        <button type="button" wire:click="backToSchools" title="Back to the schools of {{ $open->name }}" aria-label="Back to the schools of {{ $open->name }}" class="sk-back">{!! $backIcon !!}</button>
        <div class="min-w-0">
            <h1 class="sk-display truncate" style="font-size: 20px; font-weight: 800; line-height: 1.15">School Data · {{ $open->name }}</h1>
            <nav class="flex items-center gap-1 text-xs text-gray-400 min-w-0" aria-label="Breadcrumb">
                <button type="button" wire:click="backToSessions" class="hover:text-gray-700">Session</button>
                <span>/</span>
                <button type="button" wire:click="backToSchools" class="hover:text-gray-700">{{ $open->name }}</button>
                <span>/</span>
                <span class="text-gray-500 truncate">{{ $org->name }}</span>
            </nav>
        </div>
    </div>
    <div class="sk-right">
        @if ($isList)
            <button type="button" wire:click="exportData" wire:loading.attr="disabled" wire:target="exportData" class="sk-cta">
                <svg class="w-4 h-4" wire:loading.remove wire:target="exportData" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <svg class="w-4 h-4 animate-spin" wire:loading wire:target="exportData" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                Export {{ $tabs[$dataTab] }}
            </button>
        @else
            <span class="an-tag {{ $openTone }}">{{ $openWord }}</span>
        @endif
    </div>
</div>

<nav class="an-chips" aria-label="School data sections">
    @foreach ($chips as $key => [$label, $icon, $count])
        <button type="button" wire:click="setDataTab('{{ $key }}')" class="an-chip {{ $dataTab === $key ? 'on' : '' }}" aria-pressed="{{ $dataTab === $key ? 'true' : 'false' }}">
            <x-icon name="{{ $icon }}" /> {{ $label }}
            @if ($count !== null)<span class="n">{{ $num($count) }}</span>@endif
        </button>
    @endforeach
</nav>

<div class="p-4 sm:p-6 pt-4 sm:pt-5 space-y-5" wire:loading.class="opacity-60" wire:target="setDataTab">
    @if ($dataTab === 'overview')
        {{-- ══════════ The school, in the session ══════════ --}}
        <div class="sa-card p-5 sm:p-6" data-school-detail>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="sa-card-title">School Detail</h3>
                    <p class="sa-card-sub">{{ $open->name }}: {{ $open->starts_on->format('d M Y') }} – {{ $open->ends_on->format('d M Y') }}</p>
                </div>
                <div class="flex flex-wrap justify-end gap-1.5">
                    <span class="an-tag {{ $tag((bool) $org->status) }}">{{ $org->status ? 'Active' : 'Inactive' }}</span>
                    @if ($school['data']->isNew($org->created_at))
                        <span class="an-tag teal">New in {{ $open->name }}</span>
                    @endif
                </div>
            </div>
            <div class="mt-5 flex items-center gap-4 min-w-0">
                <span class="sd-logo">
                    @if ($org->logo)<img src="{{ $org->logo }}" alt="">@else{{ strtoupper(mb_substr($org->name, 0, 1)) }}@endif
                </span>
                <div class="min-w-0">
                    <h2 class="sk-display text-lg sm:text-xl font-extrabold leading-tight break-words">{{ $org->name }}</h2>
                    <p class="text-sm text-gray-500 mt-1 break-words">
                        {{ collect([$org->education_board, $org->medium ? $org->medium . ' medium' : null, $org->school_code ? 'Code ' . $org->school_code : null, $org->state])->filter()->join(' · ') ?: '—' }}
                    </p>
                </div>
            </div>
            <dl class="sd-figs mt-5">
                <div><dt>Phone</dt><dd class="words">{{ $org->mobile_number ?: '—' }}</dd></div>
                <div><dt>Email</dt><dd class="words" title="{{ $org->email }}">{{ $org->email ?: '—' }}</dd></div>
                <div><dt>Joined SuperLMS</dt><dd class="words">{{ $org->created_at?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt>Session</dt><dd class="words">{{ $open->name }} · {{ $openWord }}</dd></div>
            </dl>
        </div>

        {{-- ══════════ KPIs — for the session, month by month ══════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Students', 'value' => $num($summary['students']), 'icon' => 'users', 'color' => 'teal',
                'series' => $summary['admissions'], 'trend' => $trend($summary['admissions']), 'compare' => $compare,
                'sub' => $num($summary['newStudents']) . ' admitted in ' . $open->name . ' · ' . $num($summary['activeStudents']) . ' active',
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Teachers', 'value' => $num($summary['teachers']), 'icon' => 'academic-cap', 'color' => 'teal',
                'series' => $summary['teacherJoins'], 'trend' => $trend($summary['teacherJoins']), 'compare' => $compare,
                'sub' => $num($summary['newTeachers']) . ' joined in ' . $open->name . ' · ' . $num($summary['activeTeachers']) . ' active',
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Fees Collected', 'value' => $short($summary['collected']), 'icon' => 'banknotes', 'color' => 'teal',
                'series' => $summary['feeByMonth'], 'trend' => $trend($summary['feeByMonth']), 'compare' => $compare,
                'sub' => $num($summary['payments']) . ' ' . ($summary['payments'] === 1 ? 'payment' : 'payments') . ' in ' . $open->name,
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Attendance', 'value' => $summary['presentPct'] !== null ? $summary['presentPct'] . '%' : '—', 'icon' => 'check-badge', 'color' => 'teal',
                'series' => $summary['attendancePct'], 'trend' => null, 'flat' => false,
                'sub' => $summary['marked'] > 0
                    ? $num($summary['present']) . ' of ' . $num($summary['marked']) . ' marked present on ' . $num($summary['daysMarked']) . ' days'
                    : 'No attendance marked in ' . $open->name,
            ])
        </div>

        {{-- ══════════ Analytics — two cards to a row ══════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5" data-session-analytics>
            <div class="sa-card p-5 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Fee in {{ $open->name }}</h3>
                        <p class="sa-card-sub">What came in during the session, month by month</p>
                    </div>
                    <span class="sa-chip">{{ $short($summary['collected']) }} collected</span>
                </div>
                <dl class="sd-figs mt-4">
                    <div><dt>Collected</dt><dd class="up" title="{{ $inr($summary['collected']) }}">{{ $inr($summary['collected']) }}</dd></div>
                    <div><dt>Academic fee</dt><dd title="{{ $inr($summary['academic']) }}">{{ $inr($summary['academic']) }}</dd></div>
                    <div><dt>Bus fee</dt><dd title="{{ $inr($summary['transport']) }}">{{ $inr($summary['transport']) }}</dd></div>
                    <div><dt>Payments</dt><dd>{{ $num($summary['payments']) }}</dd></div>
                </dl>
                <p class="mt-5 text-xs font-medium text-gray-500">Collected each month</p>
                @include('livewire.super-admin.partials.month-bars', [
                    'values' => $summary['feeByMonth'], 'labels' => $monthL, 'format' => $short, 'height' => 120,
                    'empty' => 'No fee came in during ' . $open->name,
                ])
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Student Attendance</h3>
                        <p class="sa-card-sub">Present each month of the session</p>
                    </div>
                    <span class="sa-chip">{{ $num($summary['daysMarked']) }} days marked</span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <p class="sd-big">{{ $summary['presentPct'] !== null ? $summary['presentPct'] . '%' : '—' }}</p>
                    <p class="text-xs text-gray-400">present in {{ $open->name }}</p>
                </div>
                <dl class="sd-figs mt-4" style="--c: 3">
                    <div><dt>Marked</dt><dd>{{ $num($summary['marked']) }}</dd></div>
                    <div><dt>Present</dt><dd class="up">{{ $num($summary['present']) }}</dd></div>
                    <div><dt>Absent</dt><dd class="{{ $summary['marked'] - $summary['present'] > 0 ? 'down' : '' }}">{{ $num($summary['marked'] - $summary['present']) }}</dd></div>
                </dl>
                @include('livewire.super-admin.partials.month-bars', [
                    'values' => $summary['attendancePct'], 'labels' => $monthL, 'height' => 110, 'max' => 100,
                    'format' => fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.') . '%',
                    'empty' => 'No attendance marked in ' . $open->name,
                ])
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Admissions</h3>
                        <p class="sa-card-sub">Students added in each month of the session</p>
                    </div>
                    <span class="sa-chip">{{ $num($summary['newStudents']) }} new</span>
                </div>
                <dl class="sd-figs mt-4" style="--c: 3">
                    <div><dt>On the roll</dt><dd>{{ $num($summary['students']) }}</dd></div>
                    <div><dt>Active</dt><dd class="up">{{ $num($summary['activeStudents']) }}</dd></div>
                    <div><dt>Admitted in it</dt><dd>{{ $num($summary['newStudents']) }}</dd></div>
                </dl>
                @include('livewire.super-admin.partials.month-bars', [
                    'values' => $summary['admissions'], 'labels' => $monthL, 'height' => 110,
                    'empty' => 'No student added during ' . $open->name,
                ])
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Teachers &amp; Staff</h3>
                        <p class="sa-card-sub">Teachers who joined in each month of the session</p>
                    </div>
                    <span class="sa-chip">{{ $num($summary['newTeachers']) }} joined</span>
                </div>
                <dl class="sd-figs mt-4">
                    <div><dt>Teachers</dt><dd>{{ $num($summary['teachers']) }}</dd></div>
                    <div><dt>Active</dt><dd class="up">{{ $num($summary['activeTeachers']) }}</dd></div>
                    <div><dt>Staff</dt><dd>{{ $num($summary['staff']) }}</dd></div>
                    <div><dt>Joined in it</dt><dd>{{ $num($summary['newTeachers']) }}</dd></div>
                </dl>
                @include('livewire.super-admin.partials.month-bars', [
                    'values' => $summary['teacherJoins'], 'labels' => $monthL, 'height' => 110,
                    'empty' => 'No teacher joined during ' . $open->name,
                ])
            </div>

            <div class="sa-card p-5 min-w-0 lg:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Students by Class</h3>
                        <p class="sa-card-sub">The classes with the most students by the session's end</p>
                    </div>
                    <span class="sa-chip">{{ $num($summary['classes']) }} classes · {{ $num($summary['sections']) }} sections</span>
                </div>
                @php
                    $classRows = collect($school['classes'])->sortByDesc('students')->filter(fn ($c) => $c['students'] > 0)
                        ->take(8)->map(fn ($c) => ['label' => $c['name'], 'count' => $c['students']])->values()->all();
                @endphp
                @include('livewire.super-admin.partials.an-bars', ['rows' => $classRows, 'empty' => 'No students in a class yet'])
            </div>
        </div>
    @else
        {{-- ══════════ One list ══════════ --}}
        <div class="sa-card overflow-hidden" data-session-list="{{ $dataTab }}">
            @if ($dataTab === 'fees')
                <div class="px-5 pt-5">
                    <dl class="sd-figs">
                        <div><dt>Collected</dt><dd class="up">{{ $inr($summary['collected']) }}</dd></div>
                        <div><dt>Academic fee</dt><dd>{{ $inr($summary['academic']) }}</dd></div>
                        <div><dt>Bus fee</dt><dd>{{ $inr($summary['transport']) }}</dd></div>
                        <div><dt>Payments</dt><dd>{{ $num($summary['payments']) }}</dd></div>
                    </dl>
                </div>
            @endif
            @if ($searchHint)
                <div class="sk-filter">
                    <div class="sk-search">
                        <x-icon name="magnifying-glass" />
                        <input wire:model.live.debounce.300ms="dataSearch" type="text" placeholder="{{ $searchHint }}" autocomplete="off" aria-label="Search">
                    </div>
                </div>
            @endif

            <div class="relative" wire:loading.class="opacity-60" wire:target="dataSearch,gotoPage,nextPage,previousPage">
                @switch($dataTab)
                    @case('students')
                        <div class="overflow-x-auto">
                            <table class="sk-table min-w-[880px]">
                                <thead><tr><th class="w-12">#</th><th>Student</th><th>Class</th><th>Roll No.</th><th>Father</th><th>Phone</th><th>Added</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($rows as $i => $s)
                                        <tr wire:key="ssd-st-{{ $s->id }}">
                                            <td class="text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                            <td>
                                                <p class="font-semibold text-gray-900 truncate max-w-[220px]">{{ $s->full_name }}</p>
                                                <p class="text-xs text-gray-400">{{ $s->admission_no ? 'Adm. ' . $s->admission_no : ($s->user?->email ?? '') }}</p>
                                            </td>
                                            <td class="whitespace-nowrap">{{ trim(($s->standard?->name ?? '') . ($s->section ? ' - ' . $s->section->name : '')) ?: '—' }}</td>
                                            <td class="tabular-nums">{{ $s->roll_no ?: '—' }}</td>
                                            <td class="truncate max-w-[180px]">{{ $s->father_name ?: '—' }}</td>
                                            <td class="tabular-nums whitespace-nowrap">{{ $s->phone ?: '—' }}</td>
                                            <td class="text-gray-500 whitespace-nowrap">
                                                {{ $s->created_at?->format('d M Y') ?? '—' }}
                                                @if ($school['data']->isNew($s->created_at))<span class="an-tag green ml-1">New</span>@endif
                                            </td>
                                            <td><span class="an-tag {{ $tag((bool) $s->user?->is_active) }}">{{ $s->user?->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center text-sm text-gray-400" style="padding: 56px 20px">{{ $dataSearch !== '' ? 'No student matches “' . $dataSearch . '”.' : 'No students on the roll by the end of ' . $open->name . '.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @break

                    @case('teachers')
                        <div class="overflow-x-auto">
                            <table class="sk-table min-w-[820px]">
                                <thead><tr><th class="w-12">#</th><th>Teacher</th><th>Employee ID</th><th>Phone</th><th>Qualification</th><th>Joined</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($rows as $i => $t)
                                        <tr wire:key="ssd-tc-{{ $t->id }}">
                                            <td class="text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                            <td>
                                                <p class="font-semibold text-gray-900 truncate max-w-[220px]">{{ $t->user?->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ $t->user?->email }}</p>
                                            </td>
                                            <td>{{ $t->employee_id ?: '—' }}</td>
                                            <td class="tabular-nums whitespace-nowrap">{{ $t->phone ?: ($t->user?->mobile_number ?: '—') }}</td>
                                            <td class="truncate max-w-[180px]">{{ $t->qualification ?: '—' }}</td>
                                            <td class="text-gray-500 whitespace-nowrap">{{ $t->date_of_joining?->format('d M Y') ?? '—' }}</td>
                                            <td><span class="an-tag {{ $tag((bool) $t->user?->is_active) }}">{{ $t->user?->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-sm text-gray-400" style="padding: 56px 20px">{{ $dataSearch !== '' ? 'No teacher matches “' . $dataSearch . '”.' : 'No teachers by the end of ' . $open->name . '.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @break

                    @case('staff')
                        <div class="overflow-x-auto">
                            <table class="sk-table min-w-[820px]">
                                <thead><tr><th class="w-12">#</th><th>Name</th><th>Type</th><th>Designation</th><th>Mobile</th><th class="num">Salary</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($rows as $i => $e)
                                        <tr wire:key="ssd-sf-{{ $e->id }}">
                                            <td class="text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                            <td>
                                                <p class="font-semibold text-gray-900 truncate max-w-[220px]">{{ $e->name }}</p>
                                                <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ $e->email }}</p>
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::headline((string) $e->type) ?: '—' }}</td>
                                            <td class="truncate max-w-[180px]">{{ $e->designation ?: '—' }}</td>
                                            <td class="tabular-nums whitespace-nowrap">{{ $e->mobile ?: '—' }}</td>
                                            <td class="num font-semibold text-gray-900">{{ $e->salary ? $inr($e->salary) : '—' }}</td>
                                            <td><span class="an-tag {{ $tag((bool) $e->is_active) }}">{{ $e->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-sm text-gray-400" style="padding: 56px 20px">{{ $dataSearch !== '' ? 'No staff member matches “' . $dataSearch . '”.' : 'No staff besides teachers by the end of ' . $open->name . '.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @break

                    @case('classes')
                        @if (count($school['classes']))
                            <div class="p-4 sm:p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($school['classes'] as $class)
                                    <div class="ss-otp" wire:key="ssd-cl-{{ $loop->index }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate">{{ $class['name'] }}</p>
                                                <p class="text-xs text-gray-400">{{ count($class['sections']) }} {{ count($class['sections']) === 1 ? 'section' : 'sections' }}{{ $class['board'] ? ' · ' . $class['board'] : '' }}</p>
                                            </div>
                                            <span class="text-right">
                                                <span class="block text-lg font-bold text-gray-900 tabular-nums leading-tight">{{ $num($class['students']) }}</span>
                                                <span class="block text-[11px] text-gray-400">students</span>
                                            </span>
                                        </div>
                                        @if (count($class['sections']))
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @foreach ($class['sections'] as $section)
                                                    <span class="an-tag">{{ $section['name'] }} <b class="ml-1" style="color: var(--sk-teal-700)">{{ $section['students'] }}</b></span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="px-5 py-14 text-center text-sm text-gray-400">{{ $dataSearch !== '' ? 'No class matches “' . $dataSearch . '”.' : 'No classes in this school yet.' }}</p>
                        @endif
                        @break

                    @case('fees')
                        <div class="overflow-x-auto mt-4">
                            <table class="sk-table min-w-[820px]">
                                <thead><tr><th>Date</th><th>Receipt</th><th>Student</th><th>Class</th><th>Fee</th><th>Mode</th><th class="num">Amount</th></tr></thead>
                                <tbody>
                                    @forelse ($rows as $p)
                                        <tr wire:key="ssd-fe-{{ $p->id }}">
                                            <td class="text-gray-500 whitespace-nowrap">{{ $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('d M Y') : '—' }}</td>
                                            <td>{{ $p->receipt_number ?: '—' }}</td>
                                            <td>
                                                <p class="font-semibold text-gray-900 truncate max-w-[200px]">{{ $p->studentDetail?->full_name ?? '—' }}</p>
                                                @if ($p->studentDetail?->admission_no)<p class="text-xs text-gray-400">Adm. {{ $p->studentDetail->admission_no }}</p>@endif
                                            </td>
                                            <td class="whitespace-nowrap">{{ trim(($p->standard?->name ?? '') . ($p->section ? ' - ' . $p->section->name : '')) ?: '—' }}</td>
                                            <td>{{ \Illuminate\Support\Str::headline((string) $p->fee_type) ?: '—' }}</td>
                                            <td>{{ \Illuminate\Support\Str::headline((string) $p->payment_mode) ?: '—' }}</td>
                                            <td class="num font-semibold text-gray-900">{{ $inr($p->amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-sm text-gray-400" style="padding: 56px 20px">{{ $dataSearch !== '' ? 'No payment matches “' . $dataSearch . '”.' : 'No fee payments in ' . $open->name . '.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @break

                    @case('attendance')
                        <div class="overflow-x-auto">
                            <table class="sk-table min-w-[720px]">
                                <thead><tr><th>Month</th><th class="num">Days marked</th><th class="num">Marked</th><th class="num">Present</th><th class="num">Absent</th><th style="width: 30%">Present %</th></tr></thead>
                                <tbody>
                                    @forelse ($school['attendance'] as $m)
                                        <tr wire:key="ssd-at-{{ $m['key'] }}">
                                            <td class="font-semibold text-gray-900 whitespace-nowrap">{{ $m['label'] }}</td>
                                            <td class="num">{{ $num($m['days']) }}</td>
                                            <td class="num">{{ $num($m['marked']) }}</td>
                                            <td class="num text-emerald-700">{{ $num($m['present']) }}</td>
                                            <td class="num text-rose-600">{{ $num($m['absent']) }}</td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <span class="an-meter flex-1"><i style="width: {{ min(100, $m['pct']) }}%; background: #0d9488"></i></span>
                                                    <span class="text-xs font-semibold text-gray-700 tabular-nums w-12 text-right">{{ $m['pct'] }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-sm text-gray-400" style="padding: 56px 20px">No attendance was marked in {{ $open->name }}.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @break
                @endswitch

                @if ($rows && $rows->hasPages())
                    {{-- Livewire's own links: they turn this list's page (rowsPage), not the schools list's. --}}
                    <div class="px-5 py-3 border-t border-gray-100">
                        {{ $rows->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
