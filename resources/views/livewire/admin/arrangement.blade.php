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

            <span class="text-xs text-gray-500 ml-auto">
                Showing slots for <strong class="text-gray-700">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</strong>
            </span>

            @if ($filterClass)
                <button wire:click="clearFilters"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </button>
            @endif
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
    {{-- ─── Absent teacher cards ─────────────────────────── --}}
    @foreach ($absentTeachers as $teacher)
        @php
            $teacherSlots = $absentSlots->get($teacher->id, collect());
            $arrangedHere = $teacherSlots->filter(fn($s) => $arrangementsForDate->has($s->id))->count();
            $totalHere    = $teacherSlots->count();
            $pendingHere  = $totalHere - $arrangedHere;

            // Who is covering this teacher today, and how many of their periods each took.
            $coverCounts = $teacherSlots
                ->map(fn($s) => $arrangementsForDate->get($s->id)?->substituteTeacher?->user?->name)
                ->filter()
                ->countBy();
        @endphp

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            {{-- Card header --}}
            <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3.5 border-b border-gray-200 bg-gradient-to-r from-red-50/40 to-transparent">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($teacher->user?->name ?? 'T', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 truncate">{{ $teacher->user?->name ?? '—' }}</h3>
                        <p class="text-xs text-gray-500">
                            Absent today
                            @if ($totalHere > 0)
                                · <span class="text-blue-600 font-medium">{{ $arrangedHere }} of {{ $totalHere }} periods covered</span>
                            @else
                                · no periods scheduled
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if ($pendingHere > 0)
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-amber-50 text-amber-700 rounded-full font-medium border border-amber-100">
                            {{ $pendingHere }} pending
                        </span>
                    @elseif ($totalHere > 0)
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full font-medium border border-emerald-100">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Fully covered
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-red-50 text-red-700 rounded-full font-medium border border-red-100">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Absent
                    </span>
                </div>
            </div>

            {{-- Who picked up this teacher's periods today --}}
            @if ($coverCounts->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 px-4 sm:px-6 py-2.5 bg-gray-50 border-b border-gray-100">
                    <span class="text-xs font-semibold text-gray-600">Covered by:</span>
                    @foreach ($coverCounts as $name => $count)
                        <span class="inline-flex items-center gap-1.5 text-xs px-2 py-1 bg-white border border-gray-200 rounded-full text-gray-700">
                            <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[9px] font-bold">
                                {{ strtoupper(substr($name, 0, 1)) }}
                            </span>
                            {{ $name }}
                            <span class="text-gray-400">· {{ $count }} {{ $count === 1 ? 'period' : 'periods' }}</span>
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Slots — one row per period, whether or not it's arranged yet --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left w-16">Period</th>
                            <th class="px-4 py-2.5 text-left whitespace-nowrap">Time</th>
                            <th class="px-4 py-2.5 text-left">Class · Subject</th>
                            <th class="px-4 py-2.5 text-left min-w-[180px]">Covered By</th>
                            <th class="px-4 py-2.5 text-left min-w-[150px]">Remark</th>
                            <th class="px-4 py-2.5 text-center w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($teacherSlots as $i => $slot)
                            @php
                                $arrangement = $arrangementsForDate->get($slot->id);
                                $available   = $slotAvailability[$slot->id] ?? collect();
                                $editing     = $editingSlotId === (int) $slot->id;
                            @endphp
                            <tr class="align-top {{ $arrangement ? 'bg-emerald-50/40' : '' }}">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center justify-center w-7 h-6 rounded bg-gray-100 text-gray-600 text-xs font-semibold">
                                        P{{ $periodNumbers[substr($slot->start_time, 0, 5)] ?? $i + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <span class="font-medium text-gray-800">{{ $slot->standard?->name ?? '—' }}{{ $slot->section ? ' · ' . $slot->section->name : '' }}</span>
                                    <span class="text-gray-400">·</span> {{ $slot->subject?->name ?? '—' }}
                                </td>
                                @if ($arrangement && ! $editing)
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2 text-emerald-700 font-medium">
                                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                                {{ strtoupper(substr($arrangement->substituteTeacher?->user?->name ?? '—', 0, 1)) }}
                                            </span>
                                            {{ $arrangement->substituteTeacher?->user?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $arrangement->reason ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="editArrangement({{ $slot->id }})"
                                                class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-medium rounded-md transition-colors">
                                                Edit
                                            </button>
                                            <button wire:click="deleteArrangement({{ $arrangement->id }})"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Remove substitute">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                @else
                                    <td class="px-4 py-3">
                                        <select wire:model.live="slotSubstitutes.{{ $slot->id }}"
                                            class="w-full px-2.5 py-1.5 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500">
                                            <option value="">Select…</option>
                                            @foreach ($available as $sub)
                                                <option value="{{ $sub->id }}">{{ $sub->user?->name ?? '—' }}</option>
                                            @endforeach
                                        </select>
                                        @if ($available->isEmpty())
                                            <p class="text-[10px] text-amber-600 mt-1">None available.</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" wire:model="slotReasons.{{ $slot->id }}"
                                            placeholder="Reason (optional)"
                                            class="w-full px-2.5 py-1.5 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($editing)
                                                <button wire:click="updateSlot({{ $slot->id }})" wire:loading.attr="disabled" wire:target="updateSlot"
                                                    @disabled(empty($slotSubstitutes[$slot->id] ?? null))
                                                    class="px-3 py-1.5 bg-gray-900 hover:bg-gray-800 text-white text-xs font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span wire:loading.remove wire:target="updateSlot">Save</span>
                                                    <span wire:loading wire:target="updateSlot">…</span>
                                                </button>
                                                <button wire:click="cancelEdit"
                                                    class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 text-xs font-medium rounded-md">Cancel</button>
                                            @else
                                                <button wire:click="assignSlot({{ $slot->id }})" wire:loading.attr="disabled" wire:target="assignSlot"
                                                    @disabled(empty($slotSubstitutes[$slot->id] ?? null))
                                                    class="px-3 py-1.5 bg-gray-900 hover:bg-gray-800 text-white text-xs font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span wire:loading.remove wire:target="assignSlot">Assign</span>
                                                    <span wire:loading wire:target="assignSlot">…</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-400">
                                    No classes scheduled for this teacher on
                                    {{ \Carbon\Carbon::parse($date)->format('l') }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif

</div>

{{-- ═══════════════════════════════════════════════════
     DELETE CONFIRM OVERLAY (custom, no WireUI dialog)
═══════════════════════════════════════════════════ --}}
@if ($showDeleteConfirm)
<div class="fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
