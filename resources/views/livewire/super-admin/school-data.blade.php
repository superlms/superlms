<div class="min-h-screen bg-gray-50">
    @include('livewire.super-admin.partials.dash-styles')

    @php
        $inr   = fn ($v) => '₹' . number_format((float) $v, 0);
        $short = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $trend = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));
        $tabCounts = $summary ? [
            'students' => $summary['students'], 'teachers' => $summary['teachers'], 'staff' => $summary['staff'],
            'classes' => $summary['classes'], 'fees' => null,
        ] : [];
        $searchHint = [
            'students' => 'Search name, admission no., roll no., phone…',
            'teachers' => 'Search name, email, phone, employee ID…',
            'staff'    => 'Search name, designation, mobile…',
            'classes'  => 'Search a class…',
            'fees'     => 'Search receipt no., student, admission no.…',
        ][$tab];
        $statusChip = fn (bool $on) => $on ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600';
    @endphp

    {{-- ══════════ HEADER — the school, and the open list as Excel ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">School Data</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0">One school's students, teachers, staff, classes and fees</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @unless ($locked)
                    <select wire:model.live="school" aria-label="School"
                        class="text-sm bg-white border border-gray-200 rounded-lg pl-3 pr-8 py-2 text-gray-800 max-w-[18rem] focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                        @foreach ($schools as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                @endunless
                @if ($org)
                    <button wire:click="export" wire:loading.attr="disabled" wire:target="export"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 transition-colors disabled:opacity-60">
                        <svg class="w-4 h-4" wire:loading.remove wire:target="export" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <svg class="w-4 h-4 animate-spin" wire:loading wire:target="export" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Export {{ \App\Livewire\SuperAdmin\SchoolData::TABS[$tab] }}
                    </button>
                @endif
            </div>
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
            {{-- ══════════ The school ══════════ --}}
            <div class="sa-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                @if ($org->logo)
                    <img src="{{ $org->logo }}" alt="" class="w-14 h-14 rounded-2xl object-cover border border-gray-100 flex-shrink-0">
                @else
                    <span class="w-14 h-14 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center text-xl font-semibold flex-shrink-0">{{ strtoupper(mb_substr($org->name, 0, 1)) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $org->name }}</h2>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusChip((bool) $org->status) }}">{{ $org->status ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5 truncate">
                        {{ collect([$org->education_board, $org->medium ? $org->medium . ' medium' : null, $org->school_code ? 'Code ' . $org->school_code : null, $org->state])->filter()->join(' · ') ?: '—' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
                    @if ($org->mobile_number)<span class="flex items-center gap-1.5"><x-icon name="phone" class="w-4 h-4 text-gray-400" />{{ $org->mobile_number }}</span>@endif
                    @if ($org->email)<span class="flex items-center gap-1.5 min-w-0"><x-icon name="envelope" class="w-4 h-4 text-gray-400" /><span class="truncate">{{ $org->email }}</span></span>@endif
                    <span class="flex items-center gap-1.5"><x-icon name="calendar" class="w-4 h-4 text-gray-400" />Joined {{ $org->created_at?->format('d M Y') ?? '—' }}</span>
                </div>
            </div>

            {{-- ══════════ KPIs ══════════ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Students', 'value' => number_format($summary['students']), 'icon' => 'users', 'color' => 'sky',
                    'series' => $summary['admissions'], 'trend' => $trend($summary['admissions']),
                    'sub' => number_format($summary['activeStudents']) . ' active · admissions in 6 months',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Teachers', 'value' => number_format($summary['teachers']), 'icon' => 'academic-cap', 'color' => 'emerald',
                    'series' => $summary['teacherJoins'], 'trend' => $trend($summary['teacherJoins']),
                    'sub' => number_format($summary['activeTeachers']) . ' active',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Fees Collected', 'value' => $short($summary['fee']['collected']), 'icon' => 'banknotes', 'color' => 'amber',
                    'series' => $summary['feeByMonth'], 'trend' => $trend($summary['feeByMonth']),
                    'sub' => ($summary['fee']['totalFee'] > 0 ? round($summary['fee']['collected'] / $summary['fee']['totalFee'] * 100, 1) : 0) . '% of ' . $short($summary['fee']['totalFee']) . ' this session',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Present Today', 'value' => $summary['presentPct'] !== null ? $summary['presentPct'] . '%' : '—', 'icon' => 'check-badge', 'color' => 'violet',
                    'series' => $summary['attendanceWeek'], 'trend' => null, 'flat' => false,
                    'sub' => $summary['marked'] > 0 ? number_format($summary['present']) . ' of ' . number_format($summary['marked']) . ' students marked present' : 'Attendance not marked yet today',
                ])
            </div>

            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-5', 'items' => [
                ['Staff', number_format($summary['staff']), 'besides teachers', 'text-gray-900'],
                ['Classes', number_format($summary['classes']), number_format($summary['sections']) . ' sections', 'text-gray-900'],
                ['Total Fee', $inr($summary['fee']['totalFee']), 'this session', 'text-gray-900'],
                ['Collected', $inr($summary['fee']['collected']), 'since ' . \App\Support\AcademicYear::start()->format('d M Y'), 'text-emerald-600'],
                ['Pending', $inr($summary['fee']['remaining']), 'still to collect', $summary['fee']['remaining'] > 0 ? 'text-rose-600' : 'text-gray-900'],
            ]])

            {{-- ══════════ The lists ══════════ --}}
            <div class="sa-card overflow-hidden">
                <div class="px-4 sm:px-5 pt-4 pb-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-gray-100">
                    <div class="flex flex-wrap items-center gap-1.5" role="tablist">
                        @foreach (\App\Livewire\SuperAdmin\SchoolData::TABS as $key => $label)
                            <button wire:click="setTab('{{ $key }}')" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-sm font-medium rounded-full transition-colors
                                       {{ $tab === $key ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                                {{ $label }}
                                @if (($tabCounts[$key] ?? null) !== null)
                                    <span class="text-[11px] tabular-nums px-1.5 rounded-full {{ $tab === $key ? 'bg-white/20' : 'bg-gray-100 text-gray-500' }}">{{ number_format($tabCounts[$key]) }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <div class="relative w-full lg:w-80">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ $searchHint }}" autocomplete="off"
                            class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 placeholder-gray-400">
                    </div>
                </div>

                <div class="relative" wire:loading.class="opacity-60" wire:target="setTab,search,school,gotoPage,nextPage,previousPage">
                    @switch($tab)
                        {{-- ───────── Students ───────── --}}
                        @case('students')
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm min-w-[820px]">
                                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                        <tr>
                                            <th class="px-5 py-2.5 text-left font-medium w-14">#</th>
                                            <th class="px-3 py-2.5 text-left font-medium">Student</th>
                                            <th class="px-3 py-2.5 text-left font-medium">Class</th>
                                            <th class="px-3 py-2.5 text-left font-medium">Roll No.</th>
                                            <th class="px-3 py-2.5 text-left font-medium">Father</th>
                                            <th class="px-3 py-2.5 text-left font-medium">Phone</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse ($rows as $i => $s)
                                            <tr class="hover:bg-gray-50/70" wire:key="sd-st-{{ $s->id }}">
                                                <td class="px-5 py-3 text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                                <td class="px-3 py-3">
                                                    <p class="font-medium text-gray-900 truncate max-w-[220px]">{{ $s->full_name }}</p>
                                                    <p class="text-xs text-gray-400">{{ $s->admission_no ? 'Adm. ' . $s->admission_no : ($s->user?->email ?? '') }}</p>
                                                </td>
                                                <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ trim(($s->standard?->name ?? '') . ($s->section ? ' - ' . $s->section->name : '')) ?: '—' }}</td>
                                                <td class="px-3 py-3 text-gray-700 tabular-nums">{{ $s->roll_no ?: '—' }}</td>
                                                <td class="px-3 py-3 text-gray-700 truncate max-w-[180px]">{{ $s->father_name ?: '—' }}</td>
                                                <td class="px-3 py-3 text-gray-700 tabular-nums whitespace-nowrap">{{ $s->phone ?: '—' }}</td>
                                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusChip((bool) $s->user?->is_active) }}">{{ $s->user?->is_active ? 'Active' : 'Inactive' }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">{{ $search !== '' ? 'No student matches “' . $search . '”.' : 'No students in this school yet.' }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @break

                        {{-- ───────── Teachers ───────── --}}
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
                                            <tr class="hover:bg-gray-50/70" wire:key="sd-tc-{{ $t->id }}">
                                                <td class="px-5 py-3 text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                                <td class="px-3 py-3">
                                                    <p class="font-medium text-gray-900 truncate max-w-[220px]">{{ $t->user?->name ?? '—' }}</p>
                                                    <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ $t->user?->email }}</p>
                                                </td>
                                                <td class="px-3 py-3 text-gray-700">{{ $t->employee_id ?: '—' }}</td>
                                                <td class="px-3 py-3 text-gray-700 tabular-nums whitespace-nowrap">{{ $t->phone ?: ($t->user?->mobile_number ?: '—') }}</td>
                                                <td class="px-3 py-3 text-gray-700 truncate max-w-[180px]">{{ $t->qualification ?: '—' }}</td>
                                                <td class="px-3 py-3 text-gray-500 whitespace-nowrap">{{ $t->date_of_joining?->format('d M Y') ?? '—' }}</td>
                                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusChip((bool) $t->user?->is_active) }}">{{ $t->user?->is_active ? 'Active' : 'Inactive' }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">{{ $search !== '' ? 'No teacher matches “' . $search . '”.' : 'No teachers in this school yet.' }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @break

                        {{-- ───────── Staff ───────── --}}
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
                                            <tr class="hover:bg-gray-50/70" wire:key="sd-sf-{{ $e->id }}">
                                                <td class="px-5 py-3 text-gray-400 tabular-nums">{{ $rows->firstItem() + $i }}</td>
                                                <td class="px-3 py-3">
                                                    <p class="font-medium text-gray-900 truncate max-w-[220px]">{{ $e->name }}</p>
                                                    <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ $e->email }}</p>
                                                </td>
                                                <td class="px-3 py-3 text-gray-700">{{ \Illuminate\Support\Str::headline((string) $e->type) ?: '—' }}</td>
                                                <td class="px-3 py-3 text-gray-700 truncate max-w-[180px]">{{ $e->designation ?: '—' }}</td>
                                                <td class="px-3 py-3 text-gray-700 tabular-nums whitespace-nowrap">{{ $e->mobile ?: '—' }}</td>
                                                <td class="px-3 py-3 text-right text-gray-900 font-medium tabular-nums">{{ $e->salary ? $inr($e->salary) : '—' }}</td>
                                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusChip((bool) $e->is_active) }}">{{ $e->is_active ? 'Active' : 'Inactive' }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">{{ $search !== '' ? 'No staff member matches “' . $search . '”.' : 'No staff besides teachers in this school yet.' }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @break

                        {{-- ───────── Classes ───────── --}}
                        @case('classes')
                            @if (count($classes))
                                <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                                    @foreach ($classes as $class)
                                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4" wire:key="sd-cl-{{ $loop->index }}">
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
                                <p class="px-5 py-14 text-center text-sm text-gray-400">{{ $search !== '' ? 'No class matches “' . $search . '”.' : 'No classes in this school yet.' }}</p>
                            @endif
                            @break

                        {{-- ───────── Fees ───────── --}}
                        @case('fees')
                            <div class="px-4 sm:px-5 py-4 grid grid-cols-2 lg:grid-cols-4 gap-3 border-b border-gray-100">
                                @foreach ([
                                    ['Academic fee', $summary['fee']['academicFee']],
                                    ['Bus fee', $summary['fee']['transportFee']],
                                    ['Last year dues', $summary['fee']['lastYearDues']],
                                    ['Pending', $summary['fee']['remaining']],
                                ] as [$label, $amount])
                                    <div class="rounded-xl bg-gray-50 px-3.5 py-3">
                                        <p class="text-xs text-gray-400">{{ $label }}</p>
                                        <p class="text-base font-semibold tabular-nums mt-0.5 {{ $label === 'Pending' && $amount > 0 ? 'text-rose-600' : 'text-gray-900' }}">{{ $inr($amount) }}</p>
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
                                            <tr class="hover:bg-gray-50/70" wire:key="sd-fe-{{ $p->id }}">
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
                                            <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">{{ $search !== '' ? 'No payment matches “' . $search . '”.' : 'No fee payments recorded yet.' }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @break
                    @endswitch

                    @if ($rows && $rows->hasPages())
                        <div class="px-5 py-3 border-t border-gray-100">
                            {{ $rows->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
