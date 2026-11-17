<div class="min-h-screen bg-gray-50">
    @php
        $current = $sessions->firstWhere('is_current', true);
        $th = 'px-3 py-2.5 text-left text-[11px] font-medium text-gray-500 truncate';
        $td = 'px-3 py-3 truncate';
        $arrow = 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-blue-600 hover:bg-blue-50 hover:border-blue-200 transition-colors flex-shrink-0';
        $statusChip = [
            'running'   => ['Running', 'bg-emerald-50 text-emerald-700'],
            'upcoming'  => ['Upcoming', 'bg-amber-50 text-amber-700'],
            'completed' => ['Completed', 'bg-gray-100 text-gray-600'],
        ];
    @endphp

    @if (!$open)
        {{-- ══════════════════════ THE SESSIONS ══════════════════════ --}}
        @php
            $byStatus = $sessions->groupBy(fn ($s) => $s->status());
            $stats = [
                ['Sessions', number_format($sessions->count()), 'text-gray-800', null],
                ['Running', number_format(($byStatus['running'] ?? collect())->count()), 'text-emerald-600', null],
                ['Upcoming', number_format(($byStatus['upcoming'] ?? collect())->count()), 'text-amber-600', null],
                ['Completed', number_format(($byStatus['completed'] ?? collect())->count()), 'text-gray-500', null],
                ['Current', $current?->name ?? '—', 'text-blue-700', null],
            ];
        @endphp

        <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
            @include('livewire.super-admin.partials.list-header', [
                'title' => 'Session', 'stats' => $stats, 'addLabel' => 'Add Session', 'addAction' => 'openCreate',
            ])
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            {{-- ── DESKTOP TABLE — in the Schools list's look ── --}}
            <div class="hidden md:block rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full min-w-[900px] table-fixed text-[13px] text-gray-700">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="pl-4 pr-2 py-2.5 w-14 text-left text-[11px] font-medium text-gray-500 rounded-tl-xl">S.No</th>
                                <th class="{{ $th }} w-[22%]">Session</th>
                                <th class="{{ $th }}">Duration</th>
                                <th class="{{ $th }} w-[120px]">Schools</th>
                                <th class="{{ $th }} w-[110px]">Status</th>
                                <th class="{{ $th }} w-[150px]">Current Session</th>
                                <th class="px-2 py-2.5 w-[140px] text-right text-[11px] font-medium text-gray-500 rounded-tr-xl"><span class="pr-2">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sessions as $i => $session)
                                @php [$statusWord, $statusClass] = $statusChip[$session->status()]; $c = $counts[$session->id]; @endphp
                                <tr wire:key="session-{{ $session->id }}" class="{{ $session->is_current ? 'bg-blue-50/40' : 'hover:bg-gray-50/70' }} transition-colors">
                                    <td class="pl-4 pr-2 py-3 text-gray-400 tabular-nums">{{ $i + 1 }}</td>
                                    <td class="{{ $td }}">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $session->is_current ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500' }}">
                                                <x-icon name="calendar-days" class="w-4 h-4" />
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 truncate leading-tight">{{ $session->name }}</p>
                                                <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $session->months() }} {{ $session->months() === 1 ? 'month' : 'months' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="{{ $td }} text-gray-600">{{ $session->starts_on->format('d M Y') }} – {{ $session->ends_on->format('d M Y') }}</td>
                                    <td class="{{ $td }}">
                                        <span class="font-medium text-gray-900 tabular-nums">{{ number_format($c['total']) }}</span>
                                        @if ($c['new'] > 0)
                                            <span class="ml-1 text-[11px] font-medium text-emerald-600 tabular-nums" title="Registered during this session">+{{ number_format($c['new']) }} new</span>
                                        @endif
                                    </td>
                                    <td class="{{ $td }}"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusClass }}">{{ $statusWord }}</span></td>
                                    <td class="{{ $td }}">
                                        @if ($session->is_current)
                                            <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-blue-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                Current
                                            </span>
                                        @else
                                            <button wire:click="askMakeCurrent({{ $session->id }})" wire:loading.attr="disabled" wire:target="askMakeCurrent({{ $session->id }})"
                                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-md border border-gray-200 text-gray-600 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50 transition-colors disabled:opacity-60">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                <span wire:loading.remove wire:target="askMakeCurrent({{ $session->id }})">Make current</span>
                                                <span wire:loading wire:target="askMakeCurrent({{ $session->id }})">Sending codes…</span>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="edit({{ $session->id }})" title="Edit" aria-label="Edit"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                            <button wire:click="confirmDelete({{ $session->id }})" title="{{ $session->is_current ? 'The current session can’t be deleted' : 'Delete' }}" aria-label="Delete"
                                                class="p-1.5 rounded-lg transition-colors {{ $session->is_current ? 'text-gray-300 cursor-not-allowed' : 'text-red-600 hover:bg-red-50' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                            <button wire:click="openSchools({{ $session->id }})" title="Schools in {{ $session->name }}" aria-label="Schools in {{ $session->name }}" class="{{ $arrow }} ml-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center text-gray-400">
                                            <x-icon name="calendar-days" class="w-5 h-5" />
                                        </div>
                                        <p class="text-sm text-gray-500">No sessions yet. Click <strong>Add Session</strong> to add the first one.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── MOBILE CARDS ── --}}
            <div class="md:hidden space-y-3">
                @forelse ($sessions as $i => $session)
                    @php [$statusWord, $statusClass] = $statusChip[$session->status()]; $c = $counts[$session->id]; @endphp
                    <div class="bg-white rounded-xl border {{ $session->is_current ? 'border-blue-200' : 'border-gray-200' }} overflow-hidden" wire:key="session-m-{{ $session->id }}">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                            <span class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 {{ $session->is_current ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500' }}">
                                <x-icon name="calendar-days" class="w-5 h-5" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $session->name }} @if ($session->is_current)<span class="ml-1 text-[11px] font-semibold text-blue-700">· Current</span>@endif</p>
                                <p class="text-xs text-gray-400 truncate">{{ $session->starts_on->format('d M Y') }} – {{ $session->ends_on->format('d M Y') }}</p>
                            </div>
                            <button wire:click="openSchools({{ $session->id }})" aria-label="Schools in {{ $session->name }}" class="{{ $arrow }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        <div class="px-4 py-3 grid grid-cols-3 gap-2 text-sm">
                            <div>
                                <p class="text-xs text-gray-400">Schools</p>
                                <p class="text-gray-700 font-medium tabular-nums">{{ number_format($c['total']) }}@if ($c['new'] > 0) <span class="text-[11px] text-emerald-600">+{{ $c['new'] }}</span>@endif</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Months</p>
                                <p class="text-gray-700 tabular-nums">{{ $session->months() }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Status</p>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusClass }}">{{ $statusWord }}</span>
                            </div>
                        </div>
                        <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                            @unless ($session->is_current)
                                <button wire:click="askMakeCurrent({{ $session->id }})" class="flex-1 py-2.5 text-xs font-medium text-blue-700 hover:bg-blue-50">Make current</button>
                            @endunless
                            <button wire:click="edit({{ $session->id }})" class="flex-1 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</button>
                            @unless ($session->is_current)
                                <button wire:click="confirmDelete({{ $session->id }})" class="flex-1 py-2.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Delete</button>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                        <p class="text-gray-500 text-sm">No sessions yet. Tap <strong>Add Session</strong> to add the first one.</p>
                    </div>
                @endforelse
            </div>

            <p class="text-xs text-gray-400 flex items-start gap-1.5">
                <svg class="w-3.5 h-3.5 mt-px flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                The current session is the one every panel's top bar shows. Making a session current asks for two codes — one sent to the Super Admin's registered email, one on WhatsApp. The › on a session lists its schools.
            </p>
        </div>
    @elseif ($school)
        {{-- ══════════════════════ ONE SCHOOL'S DATA IN THE SESSION ══════════════════════ --}}
        @include('livewire.super-admin.partials.session-school-data', ['open' => $open, 'school' => $school, 'statusChip' => $statusChip])
    @else
        {{-- ══════════════════════ ONE SESSION'S SCHOOLS ══════════════════════ --}}
        @php
            $filtered = $schoolSearch !== '' || $schoolJoined !== '' || $schoolStatus !== '' || $schoolSort !== 'name';
            $sel = 'text-xs min-w-0 max-w-[11rem] bg-white border border-gray-200 rounded-md px-2 py-1 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
            $schoolStats = [
                ['Schools', number_format($schoolTotals['schools']), 'text-gray-800'],
                ['New this session', number_format($schoolTotals['new']), 'text-emerald-600'],
                ['Active', number_format($schoolTotals['active']), 'text-emerald-600'],
                ['Students', number_format($schoolTotals['students']), 'text-gray-800'],
                ['Teachers', number_format($schoolTotals['teachers']), 'text-gray-800'],
            ];
            [$openWord, $openClass] = $statusChip[$open->status()];
            $reloads = 'schoolSearch,schoolJoined,schoolStatus,schoolSort,gotoPage,nextPage,previousPage,clearSchoolFilters';
            $isNew = fn ($school) => $school->created_at && $school->created_at->between($start, $end);
        @endphp

        <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                    <div class="flex items-center gap-3 min-w-0">
                        <button wire:click="backToSessions" title="Back to sessions" aria-label="Back to sessions"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Schools · {{ $open->name }}</h1>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $openClass }}">{{ $openWord }}</span>
                                @if ($open->is_current)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">Current</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $open->starts_on->format('d M Y') }} – {{ $open->ends_on->format('d M Y') }} · schools on SuperLMS by the session's last day</p>
                        </div>
                    </div>
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                        @foreach ($schoolStats as [$label, $figure, $tone])
                            <span class="{{ $loop->first ? 'pr-4' : ($loop->last ? 'pl-4' : 'px-4') }} whitespace-nowrap">{{ $label }}: <strong class="{{ $tone }}">{{ $figure }}</strong></span>
                        @endforeach
                    </div>
                </div>
                <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                    @foreach ($schoolStats as [$label, $figure, $tone])
                        <span class="whitespace-nowrap">{{ $label }}: <strong class="{{ $tone }}">{{ $figure }}</strong></span>
                    @endforeach
                </div>
            </div>

            {{-- FILTER BAR — as on Schools --}}
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2" data-filter-bar>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1 text-xs font-semibold text-gray-600">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        Filter by:
                    </div>
                    <div class="relative flex-[1_1_8rem] min-w-0 max-w-52">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="schoolSearch" type="text" placeholder="Search name, email, code…"
                            class="text-xs bg-white border border-gray-200 rounded-md pl-7 pr-2.5 py-1 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <select wire:model.live="schoolJoined" class="{{ $sel }}" aria-label="Registered">
                        <option value="">All registered</option>
                        <option value="new">Registered this session</option>
                        <option value="earlier">Registered before it</option>
                    </select>
                    <select wire:model.live="schoolStatus" class="{{ $sel }}" aria-label="Status">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h12M3 12h9m-9 5h6m6 0l3 3m0 0l3-3m-3 3V8"/></svg>
                        <select wire:model.live="schoolSort" class="{{ $sel }}" aria-label="Sort">
                            <option value="name">Name (A–Z)</option>
                            <option value="newest">Newest registered</option>
                            <option value="students">Most students</option>
                        </select>
                    </div>
                    @if ($filtered)
                        <button wire:click="clearSchoolFilters"
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            {{-- ── DESKTOP TABLE — the Schools list's table; the › opens the school's data for this session ── --}}
            <div class="hidden md:block rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto xl:overflow-visible rounded-xl">
                    <table class="w-full min-w-[960px] xl:min-w-0 table-fixed text-[13px] text-gray-700">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="pl-4 pr-2 py-2.5 w-14 text-left text-[11px] font-medium text-gray-500 rounded-tl-xl">S.No</th>
                                <th class="{{ $th }} w-[28%]">School</th>
                                <th class="{{ $th }} w-[120px]">Mobile</th>
                                <th class="{{ $th }}">Board</th>
                                <th class="{{ $th }} w-[150px]">Registered</th>
                                <th class="px-3 py-2.5 text-right text-[11px] font-medium text-gray-500 truncate w-[90px]">Students</th>
                                <th class="px-3 py-2.5 text-right text-[11px] font-medium text-gray-500 truncate w-[90px]">Teachers</th>
                                <th class="px-2 py-2.5 w-[90px] text-right text-[11px] font-medium text-gray-500 rounded-tr-xl"><span class="pr-2">Data</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" wire:loading.class="opacity-60" wire:target="{{ $reloads }}">
                            @forelse ($schools as $index => $school)
                                @php $board = \App\Support\BoardName::short($school->education_board); $active = (bool) $school->status; @endphp
                                <tr wire:key="ses-sch-row-{{ $school->id }}" class="hover:bg-gray-50/70 transition-colors">
                                    <td class="pl-4 pr-2 py-3 text-gray-400 tabular-nums">{{ $schools->firstItem() + $index }}</td>
                                    <td class="{{ $td }}" title="{{ $school->name }}{{ $school->email ? ' — ' . $school->email : '' }}">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            @if ($school->logo)
                                                <img src="{{ $school->logo }}" loading="lazy" alt="" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 text-[11px] font-semibold flex items-center justify-center flex-shrink-0">{{ strtoupper(substr($school->name, 0, 1)) }}</span>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 truncate leading-tight">{{ $school->name }}</p>
                                                <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $school->email ?: '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="{{ $td }} tabular-nums">{{ $school->mobile_number ?: '—' }}</td>
                                    <td class="{{ $td }}" title="{{ $board }}">{{ $board ?: '—' }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span class="text-gray-600">{{ $school->created_at?->format('d M Y') ?? '—' }}</span>
                                        @if ($isNew($school))
                                            <span class="ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700" title="Registered during {{ $open->name }}">New</span>
                                        @endif
                                    </td>
                                    <td class="{{ $td }} text-right tabular-nums">{{ number_format($school->total_students) }}</td>
                                    <td class="{{ $td }} text-right tabular-nums">{{ number_format($school->total_teachers) }}</td>
                                    <td class="px-2 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $active ? 'bg-emerald-500' : 'bg-rose-500' }}"
                                                title="{{ $active ? 'Active' : 'Inactive' }}" role="img" aria-label="{{ $active ? 'Active' : 'Inactive' }}"></span>
                                            <button type="button" wire:click="openSchoolData({{ $school->id }})"
                                                title="{{ $school->name }} — its data in {{ $open->name }}" aria-label="{{ $school->name }} — its data in {{ $open->name }}" class="{{ $arrow }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-14 text-center">
                                        <p class="text-gray-500 text-sm">{{ $filtered ? 'No school matches these filters.' : 'No school was on SuperLMS by the end of ' . $open->name . '.' }}</p>
                                        @if ($filtered)
                                            <button wire:click="clearSchoolFilters" class="mt-2 text-xs text-gray-900 underline underline-offset-2 font-medium">Clear filters</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('livewire.super-admin.partials.list-pagination', ['paginator' => $schools])
            </div>

            {{-- ── MOBILE CARDS ── --}}
            <div class="md:hidden space-y-3">
                @forelse ($schools as $index => $school)
                    @php $active = (bool) $school->status; @endphp
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" wire:key="ses-sch-m-{{ $school->id }}">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                            <span class="text-xs font-semibold text-gray-400 w-6 text-center tabular-nums flex-shrink-0">{{ $schools->firstItem() + $index }}</span>
                            @if ($school->logo)
                                <img src="{{ $school->logo }}" alt="" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-semibold text-gray-600">{{ strtoupper(substr($school->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $school->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $school->email ?: '—' }}</p>
                            </div>
                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $active ? 'bg-emerald-500' : 'bg-rose-500' }}"
                                title="{{ $active ? 'Active' : 'Inactive' }}" role="img" aria-label="{{ $active ? 'Active' : 'Inactive' }}"></span>
                        </div>
                        <div class="px-4 py-3 grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <p class="text-xs text-gray-400">Registered</p>
                                <p class="text-gray-700">{{ $school->created_at?->format('d M Y') ?? '—' }}@if ($isNew($school)) <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700">New</span>@endif</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Students · Teachers</p>
                                <p class="text-gray-700 tabular-nums">{{ number_format($school->total_students) }} · {{ number_format($school->total_teachers) }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="openSchoolData({{ $school->id }})"
                            class="w-full flex items-center justify-center gap-1 border-t border-gray-100 py-2.5 text-xs font-medium text-blue-700 hover:bg-blue-50">
                            View {{ $open->name }} data
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                        <p class="text-gray-500 text-sm">{{ $filtered ? 'No school matches these filters.' : 'No school was on SuperLMS by the end of ' . $open->name . '.' }}</p>
                    </div>
                @endforelse

                @if ($schools->hasPages())
                    <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 px-4 py-3">
                        <p class="text-xs text-gray-500">{{ $schools->firstItem() }}–{{ $schools->lastItem() }} of {{ number_format($schools->total()) }}</p>
                        <div class="flex items-center gap-1.5">
                            @if (!$schools->onFirstPage())
                                <button wire:click="previousPage" class="h-8 px-3 text-xs text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50">Previous</button>
                            @endif
                            <span class="inline-flex items-center justify-center min-w-8 h-8 px-2 text-xs bg-gray-900 text-white rounded-lg">{{ $schools->currentPage() }}</span>
                            @if ($schools->hasMorePages())
                                <button wire:click="nextPage" class="h-8 px-3 text-xs text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50">Next</button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════ ADD / EDIT SLIDE-IN PANEL ══════════ --}}
    @if ($showPanel)
        @php $editingCurrent = $editId && $sessions->firstWhere('id', $editId)?->is_current; @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>

            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Session' : 'Add Session' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">The school year's first and last day — the name follows the dates</p>
                    </div>
                    <button wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">First day <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="startsOn"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('startsOn')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Last day <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="endsOn"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('endsOn')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Session name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live.debounce.400ms="name" maxlength="20" placeholder="e.g. 2026–27"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        @if ($editingCurrent)
                            <div class="flex items-start gap-3 p-3.5 rounded-lg border border-blue-200 bg-blue-50/60">
                                <svg class="w-4 h-4 mt-0.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm text-gray-800">This is the current session.</span>
                            </div>
                        @else
                            <label class="flex items-start gap-3 p-3.5 rounded-lg border {{ $makeCurrent ? 'border-blue-200 bg-blue-50/60' : 'border-gray-200' }} cursor-pointer transition-colors">
                                <input type="checkbox" wire:model.live="makeCurrent"
                                    class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Make this the current session</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">After saving you'll enter two codes — one sent to the Super Admin's email, one on WhatsApp.</span>
                                </span>
                            </label>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editId ? 'Update Session' : 'Save Session' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ MAKE CURRENT — the two codes on one screen ══════════ --}}
    @if ($switchId && $switching)
        @php
            $codeBox = 'w-full px-3.5 py-2.5 border rounded-lg text-center text-lg font-semibold tracking-[0.5em] tabular-nums focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500';
            $codes = [
                ['email', 'emailCode', 'Email code', 'Sent to ' . ($switchTo['email'] ?: 'the registered email'),
                    'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'bg-sky-50 text-sky-600'],
                ['whatsapp', 'whatsappCode', 'WhatsApp code', 'Sent on WhatsApp to ' . ($switchTo['whatsapp'] ?: 'the registered number'),
                    'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'bg-emerald-50 text-emerald-600'],
            ];
        @endphp
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="make-current-title">
            <div class="absolute inset-0 bg-black/40" wire:click="closeSwitch"></div>
            <form wire:submit="verifyMakeCurrent" class="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-full overflow-y-auto">
                <div class="px-6 pt-6 pb-4 text-center border-b border-gray-100">
                    <div class="mx-auto w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 id="make-current-title" class="text-base font-bold text-gray-900">Make {{ $switching->name }} the current session</h3>
                    <p class="text-sm text-gray-500 mt-1">Two different codes were sent to the Super Admin — enter both to confirm.</p>
                    @if ($current)
                        <p class="text-xs text-gray-400 mt-2">Now current: <strong class="text-gray-600">{{ $current->name }}</strong> → after this: <strong class="text-blue-700">{{ $switching->name }}</strong></p>
                    @endif
                </div>

                <div class="px-6 py-5 space-y-5">
                    @foreach ($codes as [$channel, $field, $label, $where, $icon, $tile])
                        @php $left = max(0, ($resendAt[$channel] ?? 0) - now()->timestamp); @endphp
                        <div>
                            <div class="flex items-center gap-2.5 mb-2">
                                <span class="w-8 h-8 rounded-lg {{ $tile }} flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <label for="code-{{ $channel }}" class="block text-sm font-medium text-gray-800">{{ $label }}</label>
                                    <p class="text-xs text-gray-400 truncate">{{ $where }}</p>
                                </div>
                                <div class="text-xs flex-shrink-0" wire:key="resend-{{ $channel }}-{{ $resendAt[$channel] ?? 0 }}"
                                    x-data="{ left: {{ $left }} }" x-init="const t = setInterval(() => { left > 0 ? left-- : clearInterval(t) }, 1000)">
                                    <span x-show="left > 0" class="text-gray-400 tabular-nums" x-text="'Resend in ' + left + 's'"></span>
                                    <button type="button" x-show="left <= 0" x-cloak wire:click="resendCode('{{ $channel }}')" wire:loading.attr="disabled" wire:target="resendCode('{{ $channel }}')"
                                        class="font-medium text-blue-600 hover:text-blue-800 disabled:opacity-50">Resend</button>
                                </div>
                            </div>
                            <input id="code-{{ $channel }}" type="text" wire:model="{{ $field }}" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                                autocomplete="one-time-code" placeholder="••••••" @if ($loop->first) autofocus @endif
                                class="{{ $codeBox }} {{ $errors->has($field) ? 'border-red-300 bg-red-50/40' : 'border-gray-300' }}">
                            @error($field)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @endforeach

                    @error('request')
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-3.5 py-2.5 text-xs text-amber-800">{{ $message }}</div>
                    @enderror
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-2">
                    <button type="button" wire:click="closeSwitch" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="verifyMakeCurrent"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-60">
                        <span wire:loading.remove wire:target="verifyMakeCurrent">Verify &amp; make current</span>
                        <span wire:loading wire:target="verifyMakeCurrent">Checking…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($deleteId)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 19h14.14A2 2 0 0021 17.05L13.86 4.9a2 2 0 00-3.46 0L3.07 17.05A2 2 0 004.93 19z"/></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">Delete this session?</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $sessions->firstWhere('id', $deleteId)?->name }} will be removed. This can't be undone.</p>
                <div class="flex items-center justify-center gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="delete" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
