<div class="min-h-screen bg-gray-50">

{{-- ══════════════════════════════════════════════════
     HEADER + FILTER BAR (exams-style)
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Teacher Arrangements</h1>
            </div>
            <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalTeachers }}</strong></span>
                <span class="px-4">Absent: <strong class="text-red-500">{{ $absentCount }}</strong></span>
                <span class="px-4">Available: <strong class="text-emerald-600">{{ $availableCount }}</strong></span>
                <span class="pl-4">Arranged: <strong class="text-blue-600">{{ $arrangementCount }}</strong></span>
            </div>
        </div>
        <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Total: <strong class="text-gray-800">{{ $totalTeachers }}</strong></span>
            <span>Absent: <strong class="text-red-500">{{ $absentCount }}</strong></span>
            <span>Available: <strong class="text-emerald-600">{{ $availableCount }}</strong></span>
            <span>Arranged: <strong class="text-blue-600">{{ $arrangementCount }}</strong></span>
        </div>
    </div>

    {{-- Filter bar (exams-style) --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter by:
            </div>

            <input type="date" wire:model.live="date"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[150px]">

            <select wire:model.live="filterClass"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                <option value="">All Classes</option>
                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
            </select>

            {{-- Clear sits right after the filters (as on every page): a class
                 picked, or a day other than today, goes back to today, all classes. --}}
            @if ($filterClass || $date !== now()->format('Y-m-d'))
                <button wire:click="clearFilters"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </button>
            @endif

            <span class="text-xs text-gray-500 ml-auto">
                Showing slots for <strong class="text-gray-700">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</strong>
            </span>
        </div>
    </div>
</div>

<div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

@if ($absentTeachers->isEmpty())
    {{-- ─── Empty state: nobody absent ───────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
        <div class="w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-sm text-gray-600 font-medium">No teachers marked absent for this date.</p>
        <p class="text-xs text-gray-400 mt-1">Mark attendance to begin arranging substitutes.</p>
    </div>
@else
    {{-- ─── The absent teachers, in the Students list's table ───
         One row a teacher: their periods that day, how many are covered and
         by whom; the status dot (green all covered, red some pending) and the
         pencil, which opens their periods in the slide-in to arrange. --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Teacher</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Periods</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Covered</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Covered By</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($absentTeachers as $ti => $teacher)
                        @php
                            $teacherSlots = $absentSlots->get($teacher->id, collect());
                            $arrangedHere = $teacherSlots->filter(fn($s) => $arrangementsForDate->has($s->id))->count();
                            $totalHere    = $teacherSlots->count();
                            $pendingHere  = $totalHere - $arrangedHere;
                            $coverCounts  = $teacherSlots
                                ->map(fn($s) => $arrangementsForDate->get($s->id)?->substituteTeacher?->user?->name)
                                ->filter()
                                ->countBy();
                            $tName = $teacher->user?->name ?? '—';
                            $tImg  = $teacher->user?->image;
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition-colors" wire:key="arr-t-{{ $teacher->id }}">
                            <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $ti + 1 }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($tImg)
                                        <img src="{{ $tImg }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($tName, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $tName }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $teacher->user?->username ?: ($teacher->user?->email ?? '') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-700">{{ $totalHere }} {{ $totalHere === 1 ? 'period' : 'periods' }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($totalHere === 0)
                                    <span class="text-sm text-gray-400">No periods today</span>
                                @else
                                    <span class="text-sm text-gray-700 tabular-nums">{{ $arrangedHere }} of {{ $totalHere }}</span>
                                    <p class="text-xs {{ $pendingHere > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $pendingHere > 0 ? $pendingHere . ' pending' : 'All covered' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($coverCounts->isNotEmpty())
                                    <span class="text-sm text-gray-700">
                                        {{ $coverCounts->map(fn ($count, $name) => $name . ($count > 1 ? ' (' . $count . ')' : ''))->implode(', ') }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 mr-1 {{ $totalHere === 0 ? 'bg-gray-300' : ($pendingHere > 0 ? 'bg-red-500' : 'bg-green-500') }}"
                                        title="{{ $totalHere === 0 ? 'No periods' : ($pendingHere > 0 ? $pendingHere . ' pending' : 'All covered') }}"></span>
                                    <button wire:click="openArrange({{ $teacher->id }})" title="Arrange"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

</div>

{{-- ═══════════════════════════════════════════════════
     ARRANGE SLIDE-IN — the Mark Attendance panel's look
     One absent teacher's periods that day, a plain row each: the period, its
     time, class and subject, then who covers it — a substitute to pick (only
     those free then) with a remark and Assign, or the one arranged with Edit
     and Remove. Same assign / edit / remove as before.
═══════════════════════════════════════════════════ --}}
@php
    $arrTeacher = $arrangeTeacherId ? $absentTeachers->firstWhere('id', $arrangeTeacherId) : null;
@endphp
@if ($arrTeacher)
    @php
        $panelSlots   = $absentSlots->get($arrTeacher->id, collect());
        $panelCovered = $panelSlots->filter(fn($s) => $arrangementsForDate->has($s->id))->count();
    @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeArrange"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">Arrange · {{ $arrTeacher->user?->name ?? '—' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }} · absent</p>
                </div>
                <button wire:click="closeArrange" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Toolbar line --}}
            <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0 text-xs text-gray-500">
                <span>{{ $panelSlots->count() }} {{ $panelSlots->count() === 1 ? 'period' : 'periods' }}{{ $filterClass ? ' in the class picked' : '' }}</span>
                <span class="ml-auto tabular-nums">{{ $panelCovered }} of {{ $panelSlots->count() }} covered</span>
            </div>
            <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">Only teachers who are present and free at that time are offered.</p>

            {{-- Rows --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @forelse ($panelSlots as $i => $slot)
                    @php
                        $arrangement = $arrangementsForDate->get($slot->id);
                        $available   = $slotAvailability[$slot->id] ?? collect();
                        $editing     = $editingSlotId === (int) $slot->id;
                        $pNo         = $periodNumbers[substr($slot->start_time, 0, 5)] ?? $i + 1;
                    @endphp
                    <div wire:key="arr-slot-{{ $slot->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-6 py-2.5 {{ $arrangement && !$editing ? 'bg-emerald-50/30' : '' }}">
                        <span class="w-7 text-[11px] font-semibold text-gray-500 tabular-nums flex-shrink-0">P{{ $pNo }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 truncate">{{ $slot->standard?->name ?? '—' }}{{ $slot->section ? ' · ' . $slot->section->name : '' }} · {{ $slot->subject?->name ?? '—' }}</p>
                            <p class="text-[11px] text-gray-400 tabular-nums">{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</p>
                        </div>

                        @if ($arrangement && !$editing)
                            <div class="min-w-0 text-right">
                                <p class="text-sm text-emerald-700 truncate">{{ $arrangement->substituteTeacher?->user?->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400 truncate">{{ $arrangement->reason ?: 'No remark' }}</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button wire:click="editArrangement({{ $slot->id }})" title="Edit"
                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="deleteArrangement({{ $arrangement->id }})" title="Remove substitute"
                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        @else
                            <div class="flex-shrink-0">
                                <select wire:model.live="slotSubstitutes.{{ $slot->id }}"
                                    class="w-40 text-xs border border-gray-200 rounded-md px-2.5 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                    <option value="">{{ $available->isEmpty() ? 'None available' : 'Substitute…' }}</option>
                                    @foreach ($available as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->user?->name ?? '—' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="text" wire:model="slotReasons.{{ $slot->id }}" placeholder="Remark"
                                class="w-28 sm:w-36 text-xs border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            <div class="flex items-center gap-1 flex-shrink-0">
                                @if ($editing)
                                    <button wire:click="updateSlot({{ $slot->id }})" wire:loading.attr="disabled" wire:target="updateSlot"
                                        @disabled(empty($slotSubstitutes[$slot->id] ?? null))
                                        class="px-3 py-1.5 bg-gray-900 hover:bg-gray-800 text-white text-xs font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span wire:loading.remove wire:target="updateSlot">Save</span>
                                        <span wire:loading wire:target="updateSlot">…</span>
                                    </button>
                                    <button wire:click="cancelEdit" class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 text-xs font-medium rounded-md">Cancel</button>
                                @else
                                    <button wire:click="assignSlot({{ $slot->id }})" wire:loading.attr="disabled" wire:target="assignSlot"
                                        @disabled(empty($slotSubstitutes[$slot->id] ?? null))
                                        class="px-3 py-1.5 bg-gray-900 hover:bg-gray-800 text-white text-xs font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span wire:loading.remove wire:target="assignSlot">Assign</span>
                                        <span wire:loading wire:target="assignSlot">…</span>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="py-16 text-center text-sm text-gray-400">No classes scheduled for this teacher on {{ \Carbon\Carbon::parse($date)->format('l') }}.</p>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeArrange" type="button" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Done</button>
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════
     DELETE CONFIRM OVERLAY (custom, no WireUI dialog)
═══════════════════════════════════════════════════ --}}
@if ($showDeleteConfirm)
<div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Remove this arrangement?</h3>
                <p class="text-sm text-gray-500">
                    <strong>{{ $deleteTargetLabel }}</strong> will be unassigned. The slot will become unarranged again.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="confirmDelete" wire:loading.attr="disabled"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                <span wire:loading.remove wire:target="confirmDelete">Remove</span>
                <span wire:loading wire:target="confirmDelete">Removing…</span>
            </button>
        </div>
    </div>
</div>
@endif

</div>
