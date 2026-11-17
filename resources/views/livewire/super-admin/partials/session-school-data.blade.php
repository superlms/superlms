{{-- Super Admin → Session → a session's schools → a school's ›: all its data
     in that session (App\Support\SessionSchoolData), in School Data's look —
     the school, its figures for the session, then each list with a search and
     an Excel export. $open (the session), $school ['org', 'data', 'summary',
     'rows', 'classes', 'attendance'], $statusChip. --}}
@include('livewire.super-admin.partials.dash-styles')
@php
    $org     = $school['org'];
    $summary = $school['summary'];
    $rows    = $school['rows'];
    $inr     = fn ($v) => '₹' . number_format((float) $v, 0);
    $short   = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
    $trend   = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));
    $tabs    = \App\Support\SessionSchoolData::TABS;
    $tabCounts = [
        'students' => $summary['students'], 'teachers' => $summary['teachers'], 'staff' => $summary['staff'],
        'classes' => $summary['classes'], 'fees' => $summary['payments'], 'attendance' => null,
    ];
    $searchHint = [
        'students'   => 'Search name, admission no., roll no., phone…',
        'teachers'   => 'Search name, email, phone, employee ID…',
        'staff'      => 'Search name, designation, mobile…',
        'classes'    => 'Search a class…',
        'fees'       => 'Search receipt no., student, admission no.…',
        'attendance' => null,
    ][$dataTab];
    $chip = fn (bool $on) => $on ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600';
    [$openWord, $openClass] = $statusChip[$open->status()];
    $compare = 'vs the month before';
@endphp

{{-- ══════════ HEADER — back to the session's schools; the open list as Excel ══════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
        <div class="flex items-center gap-3 min-w-0">
            <button wire:click="backToSchools" title="Back to the schools of {{ $open->name }}" aria-label="Back to the schools of {{ $open->name }}"
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="min-w-0">
                <nav class="flex items-center gap-1 text-xs text-gray-400" aria-label="Breadcrumb">
                    <button wire:click="backToSessions" class="hover:text-gray-700">Session</button>
                    <span>/</span>
                    <button wire:click="backToSchools" class="hover:text-gray-700">{{ $open->name }}</button>
                    <span>/</span>
                    <span class="text-gray-500 truncate">{{ $org->name }}</span>
                </nav>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">School Data · {{ $open->name }}</h1>
            </div>
        </div>
        <button wire:click="exportData" wire:loading.attr="disabled" wire:target="exportData"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 transition-colors disabled:opacity-60 self-start sm:self-auto">
            <svg class="w-4 h-4" wire:loading.remove wire:target="exportData" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <svg class="w-4 h-4 animate-spin" wire:loading wire:target="exportData" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            Export {{ $tabs[$dataTab] }}
        </button>
    </div>
</div>

<div class="p-4 sm:p-6 space-y-5">
    {{-- ══════════ The school, in the session ══════════ --}}
    <div class="sa-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        @if ($org->logo)
            <img src="{{ $org->logo }}" alt="" class="w-14 h-14 rounded-2xl object-cover border border-gray-100 flex-shrink-0">
        @else
            <span class="w-14 h-14 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center text-xl font-semibold flex-shrink-0">{{ strtoupper(mb_substr($org->name, 0, 1)) }}</span>
        @endif
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $org->name }}</h2>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $chip((bool) $org->status) }}">{{ $org->status ? 'Active' : 'Inactive' }}</span>
                @if ($school['data']->isNew($org->created_at))
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">New in {{ $open->name }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-0.5 truncate">
                {{ collect([$org->education_board, $org->medium ? $org->medium . ' medium' : null, $org->school_code ? 'Code ' . $org->school_code : null, $org->state])->filter()->join(' · ') ?: '—' }}
            </p>
        </div>
        <div class="flex flex-col gap-1 text-sm text-gray-500 sm:text-right">
            <span class="inline-flex items-center gap-1.5 sm:justify-end">
                <x-icon name="calendar-days" class="w-4 h-4 text-gray-400" />
                {{ $open->name }}: {{ $open->starts_on->format('d M Y') }} – {{ $open->ends_on->format('d M Y') }}
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $openClass }}">{{ $openWord }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 sm:justify-end"><x-icon name="calendar" class="w-4 h-4 text-gray-400" />Joined SuperLMS {{ $org->created_at?->format('d M Y') ?? '—' }}</span>
        </div>
    </div>

    {{-- ══════════ KPIs — for the session, month by month ══════════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        @include('livewire.super-admin.partials.dash-kpi', [
            'label' => 'Students', 'value' => number_format($summary['students']), 'icon' => 'users', 'color' => 'sky',
            'series' => $summary['admissions'], 'trend' => $trend($summary['admissions']), 'compare' => $compare,
            'sub' => number_format($summary['newStudents']) . ' admitted in ' . $open->name . ' · ' . number_format($summary['activeStudents']) . ' active',
        ])
        @include('livewire.super-admin.partials.dash-kpi', [
            'label' => 'Teachers', 'value' => number_format($summary['teachers']), 'icon' => 'academic-cap', 'color' => 'emerald',
            'series' => $summary['teacherJoins'], 'trend' => $trend($summary['teacherJoins']), 'compare' => $compare,
            'sub' => number_format($summary['newTeachers']) . ' joined in ' . $open->name . ' · ' . number_format($summary['activeTeachers']) . ' active',
        ])
        @include('livewire.super-admin.partials.dash-kpi', [
            'label' => 'Fees Collected', 'value' => $short($summary['collected']), 'icon' => 'banknotes', 'color' => 'amber',
            'series' => $summary['feeByMonth'], 'trend' => $trend($summary['feeByMonth']), 'compare' => $compare,
            'sub' => number_format($summary['payments']) . ' ' . ($summary['payments'] === 1 ? 'payment' : 'payments') . ' in ' . $open->name,
        ])
        @include('livewire.super-admin.partials.dash-kpi', [
            'label' => 'Attendance', 'value' => $summary['presentPct'] !== null ? $summary['presentPct'] . '%' : '—', 'icon' => 'check-badge', 'color' => 'violet',
            'series' => $summary['attendancePct'], 'trend' => null, 'flat' => false,
            'sub' => $summary['marked'] > 0
                ? number_format($summary['present']) . ' of ' . number_format($summary['marked']) . ' marked present on ' . number_format($summary['daysMarked']) . ' days'
                : 'No attendance marked in ' . $open->name,
        ])
    </div>

    @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-5', 'items' => [
        ['Staff', number_format($summary['staff']), 'besides teachers', 'text-gray-900'],
        ['Classes', number_format($summary['classes']), number_format($summary['sections']) . ' sections', 'text-gray-900'],
        ['New Admissions', number_format($summary['newStudents']), 'in ' . $open->name, 'text-emerald-600'],
        ['Academic Fee', $inr($summary['academic']), 'came in during ' . $open->name, 'text-emerald-600'],
        ['Bus Fee', $inr($summary['transport']), 'came in during ' . $open->name, 'text-gray-900'],
    ]])

    {{-- ══════════ The lists ══════════ --}}
    <div class="sa-card overflow-hidden">
        <div class="px-4 sm:px-5 pt-4 pb-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-gray-100">
            <div class="flex flex-wrap items-center gap-1.5" role="tablist">
                @foreach ($tabs as $key => $label)
                    <button wire:click="setDataTab('{{ $key }}')" role="tab" aria-selected="{{ $dataTab === $key ? 'true' : 'false' }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-sm font-medium rounded-full transition-colors
                               {{ $dataTab === $key ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        {{ $label }}
                        @if (($tabCounts[$key] ?? null) !== null)
                            <span class="text-[11px] tabular-nums px-1.5 rounded-full {{ $dataTab === $key ? 'bg-white/20' : 'bg-gray-100 text-gray-500' }}">{{ number_format($tabCounts[$key]) }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
            @if ($searchHint)
                <div class="relative w-full lg:w-80">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                    <input wire:model.live.debounce.300ms="dataSearch" type="text" placeholder="{{ $searchHint }}" autocomplete="off"
                        class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 placeholder-gray-400">
                </div>
            @endif
        </div>

        <div class="relative" wire:loading.class="opacity-60" wire:target="setDataTab,dataSearch,gotoPage,nextPage,previousPage">
            @switch($dataTab)
                @case('students')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[860px]">
                            <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-medium w-14">#</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Student</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Class</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Roll No.</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Father</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Phone</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Added</th>
                                    <th class="px-5 py-2.5 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $i => $s)
                                    <tr class="hover:bg-gray-50/70" wire:key="ssd-st-{{ $s->id }}">
                                        <td class="px-5 py-3 text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-gray-900 truncate max-w-[220px]">{{ $s->full_name }}</p>
                                            <p class="text-xs text-gray-400">{{ $s->admission_no ? 'Adm. ' . $s->admission_no : ($s->user?->email ?? '') }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ trim(($s->standard?->name ?? '') . ($s->section ? ' - ' . $s->section->name : '')) ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700 tabular-nums">{{ $s->roll_no ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700 truncate max-w-[180px]">{{ $s->father_name ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700 tabular-nums whitespace-nowrap">{{ $s->phone ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-500 whitespace-nowrap">
                                            {{ $s->created_at?->format('d M Y') ?? '—' }}
                                            @if ($school['data']->isNew($s->created_at))<span class="ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700">New</span>@endif
                                        </td>
                                        <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $chip((bool) $s->user?->is_active) }}">{{ $s->user?->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="px-5 py-14 text-center text-sm text-gray-400">{{ $dataSearch !== '' ? 'No student matches “' . $dataSearch . '”.' : 'No students on the roll by the end of ' . $open->name . '.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @break

                @case('teachers')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[820px]">
                            <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-medium w-14">#</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Teacher</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Employee ID</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Phone</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Qualification</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Joined</th>
                                    <th class="px-5 py-2.5 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $i => $t)
                                    <tr class="hover:bg-gray-50/70" wire:key="ssd-tc-{{ $t->id }}">
                                        <td class="px-5 py-3 text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-gray-900 truncate max-w-[220px]">{{ $t->user?->name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ $t->user?->email }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-gray-700">{{ $t->employee_id ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700 tabular-nums whitespace-nowrap">{{ $t->phone ?: ($t->user?->mobile_number ?: '—') }}</td>
                                        <td class="px-3 py-3 text-gray-700 truncate max-w-[180px]">{{ $t->qualification ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-500 whitespace-nowrap">{{ $t->date_of_joining?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $chip((bool) $t->user?->is_active) }}">{{ $t->user?->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">{{ $dataSearch !== '' ? 'No teacher matches “' . $dataSearch . '”.' : 'No teachers by the end of ' . $open->name . '.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @break

                @case('staff')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[820px]">
                            <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-medium w-14">#</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Name</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Type</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Designation</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Mobile</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Salary</th>
                                    <th class="px-5 py-2.5 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $i => $e)
                                    <tr class="hover:bg-gray-50/70" wire:key="ssd-sf-{{ $e->id }}">
                                        <td class="px-5 py-3 text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-gray-900 truncate max-w-[220px]">{{ $e->name }}</p>
                                            <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ $e->email }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-gray-700">{{ \Illuminate\Support\Str::headline((string) $e->type) ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700 truncate max-w-[180px]">{{ $e->designation ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700 tabular-nums whitespace-nowrap">{{ $e->mobile ?: '—' }}</td>
                                        <td class="px-3 py-3 text-right text-gray-900 font-medium tabular-nums">{{ $e->salary ? $inr($e->salary) : '—' }}</td>
                                        <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $chip((bool) $e->is_active) }}">{{ $e->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">{{ $dataSearch !== '' ? 'No staff member matches “' . $dataSearch . '”.' : 'No staff besides teachers by the end of ' . $open->name . '.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @break

                @case('classes')
                    @if (count($school['classes']))
                        <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($school['classes'] as $class)
                                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4" wire:key="ssd-cl-{{ $loop->index }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate">{{ $class['name'] }}</p>
                                            <p class="text-xs text-gray-400">{{ count($class['sections']) }} {{ count($class['sections']) === 1 ? 'section' : 'sections' }}{{ $class['board'] ? ' · ' . $class['board'] : '' }}</p>
                                        </div>
                                        <span class="text-right">
                                            <span class="block text-lg font-semibold text-gray-900 tabular-nums leading-tight">{{ number_format($class['students']) }}</span>
                                            <span class="block text-[11px] text-gray-400">students</span>
                                        </span>
                                    </div>
                                    @if (count($class['sections']))
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @foreach ($class['sections'] as $section)
                                                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-white border border-gray-200 text-gray-700">
                                                    {{ $section['name'] }}
                                                    <span class="text-violet-600 font-semibold tabular-nums">{{ $section['students'] }}</span>
                                                </span>
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
                    <div class="px-4 sm:px-5 py-4 grid grid-cols-2 lg:grid-cols-4 gap-3 border-b border-gray-100">
                        @foreach ([
                            ['Collected', $inr($summary['collected'])],
                            ['Academic fee', $inr($summary['academic'])],
                            ['Bus fee', $inr($summary['transport'])],
                            ['Payments', number_format($summary['payments'])],
                        ] as [$label, $figure])
                            <div class="rounded-xl bg-gray-50 px-3.5 py-3">
                                <p class="text-xs text-gray-400">{{ $label }}</p>
                                <p class="text-base font-semibold tabular-nums mt-0.5 {{ $label === 'Collected' ? 'text-emerald-600' : 'text-gray-900' }}">{{ $figure }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[820px]">
                            <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-medium">Date</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Receipt</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Student</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Class</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Fee</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Mode</th>
                                    <th class="px-5 py-2.5 text-right font-medium">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $p)
                                    <tr class="hover:bg-gray-50/70" wire:key="ssd-fe-{{ $p->id }}">
                                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('d M Y') : '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700">{{ $p->receipt_number ?: '—' }}</td>
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-gray-900 truncate max-w-[200px]">{{ $p->studentDetail?->full_name ?? '—' }}</p>
                                            @if ($p->studentDetail?->admission_no)<p class="text-xs text-gray-400">Adm. {{ $p->studentDetail->admission_no }}</p>@endif
                                        </td>
                                        <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ trim(($p->standard?->name ?? '') . ($p->section ? ' - ' . $p->section->name : '')) ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700">{{ \Illuminate\Support\Str::headline((string) $p->fee_type) ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700">{{ \Illuminate\Support\Str::headline((string) $p->payment_mode) ?: '—' }}</td>
                                        <td class="px-5 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ $inr($p->amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">{{ $dataSearch !== '' ? 'No payment matches “' . $dataSearch . '”.' : 'No fee payments in ' . $open->name . '.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @break

                @case('attendance')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[720px]">
                            <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-medium">Month</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Days marked</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Marked</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Present</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Absent</th>
                                    <th class="px-5 py-2.5 text-left font-medium w-[30%]">Present %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($school['attendance'] as $m)
                                    <tr class="hover:bg-gray-50/70" wire:key="ssd-at-{{ $m['key'] }}">
                                        <td class="px-5 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $m['label'] }}</td>
                                        <td class="px-3 py-3 text-right text-gray-700 tabular-nums">{{ number_format($m['days']) }}</td>
                                        <td class="px-3 py-3 text-right text-gray-700 tabular-nums">{{ number_format($m['marked']) }}</td>
                                        <td class="px-3 py-3 text-right text-emerald-700 tabular-nums">{{ number_format($m['present']) }}</td>
                                        <td class="px-3 py-3 text-right text-rose-600 tabular-nums">{{ number_format($m['absent']) }}</td>
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden"><span class="block h-full rounded-full bg-violet-500" style="width: {{ min(100, $m['pct']) }}%"></span></span>
                                                <span class="text-xs font-semibold text-gray-700 tabular-nums w-12 text-right">{{ $m['pct'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-gray-400">No attendance was marked in {{ $open->name }}.</td></tr>
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
</div>
