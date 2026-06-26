<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (compact, fees/about-app style) + tabs
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Exam Seating Management</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Rooms, invigilators &amp; auto-generated exam seating plans</p>
                </div>
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Rooms: <strong class="text-gray-800">{{ $rooms->count() }}</strong></span>
                    <span class="px-4">Invigilators: <strong class="text-emerald-600">{{ $invigilators->count() }}</strong></span>
                    <span class="pl-4">Plans: <strong class="text-blue-600">{{ $plans->total() }}</strong></span>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1 overflow-x-auto">
                @php
                    $tabs = [
                        'plans'        => 'Seating Plans',
                        'rooms'        => 'Rooms',
                        'invigilators' => 'Invigilators',
                    ];
                @endphp
                @foreach ($tabs as $key => $label)
                    <button wire:click="switchTab('{{ $key }}')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                               {{ $activeTab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">

        {{-- ══════════════════════════════════════════════
             TAB: SEATING PLANS
        ══════════════════════════════════════════════ --}}
        @if ($activeTab === 'plans')
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <input wire:model.live.debounce.300ms="planSearch" type="text" placeholder="Search plans…"
                    class="text-sm bg-white border border-gray-200 rounded-md px-3 py-2 text-gray-700 w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <button wire:click="openGeneratePanel"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Generate Plan
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($plans as $plan)
                    <div class="bg-white rounded-xl border border-gray-200 hover:border-blue-200 hover:shadow-md transition-all overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900 truncate">{{ $plan->name }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $plan->exam->exam_name ?? '—' }}</p>
                            </div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide flex-shrink-0
                                {{ $plan->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $plan->status }}
                            </span>
                        </div>
                        <div class="px-5 py-4 space-y-2">
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $plan->exam_date?->format('d M Y') }}{{ $plan->session ? ' · ' . ucfirst($plan->session) : '' }}
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center pt-1">
                                <div class="bg-blue-50 rounded-lg py-2">
                                    <p class="text-base font-bold text-blue-700">{{ $plan->total_students }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-blue-500">Students</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg py-2">
                                    <p class="text-base font-bold text-gray-700">{{ $plan->total_seats }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-500">Seats</p>
                                </div>
                                <div class="rounded-lg py-2 {{ $plan->conflict_count > 0 ? 'bg-red-50' : 'bg-emerald-50' }}">
                                    <p class="text-base font-bold {{ $plan->conflict_count > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $plan->conflict_count }}</p>
                                    <p class="text-[10px] uppercase tracking-wide {{ $plan->conflict_count > 0 ? 'text-red-500' : 'text-emerald-500' }}">Conflicts</p>
                                </div>
                            </div>
                        </div>
                        <div class="px-5 py-3 border-t border-gray-100 flex items-center gap-1.5">
                            <button wire:click="viewPlan({{ $plan->id }})"
                                class="flex-1 text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">View</button>
                            <a href="{{ route('admin.seating-plan.print', $plan->id) }}" target="_blank"
                                class="flex-1 text-center text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50">Print</a>
                            @if ($plan->status !== 'published')
                                <button wire:click="publishPlan({{ $plan->id }})"
                                    class="text-xs font-medium px-3 py-1.5 rounded-md border border-emerald-200 text-emerald-600 hover:bg-emerald-50">Publish</button>
                            @endif
                            <button wire:click="confirmDeletePlan({{ $plan->id }})"
                                class="px-2 py-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200">
                        <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>
                        <p class="text-base font-semibold text-gray-800">No seating plans yet</p>
                        <p class="text-sm text-gray-400 mt-1">Add rooms &amp; invigilators, then generate a plan.</p>
                    </div>
                @endforelse
            </div>

            @if ($plans->hasPages())
                <div class="mt-6">{{ $plans->links() }}</div>
            @endif
        @endif

        {{-- ══════════════════════════════════════════════
             TAB: ROOMS
        ══════════════════════════════════════════════ --}}
        @if ($activeTab === 'rooms')
            <div class="flex items-center justify-between mb-5">
                <p class="text-sm text-gray-500">{{ $rooms->count() }} room(s) configured</p>
                <button wire:click="openRoomPanel"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Room
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($rooms as $room)
                    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-200 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900 truncate">{{ $room->room_name }}</h3>
                                @if ($room->building)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $room->building }}</p>
                                @endif
                            </div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $room->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $room->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 mt-3 text-sm text-gray-600">
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                {{ $room->rows }} × {{ $room->columns }}
                            </span>
                            <span class="text-gray-300">•</span>
                            <span class="font-semibold text-gray-800">{{ $room->capacity }} seats</span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-4 pt-3 border-t border-gray-100">
                            <button wire:click="openRoomPanel({{ $room->id }})"
                                class="flex-1 text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">Edit</button>
                            <button wire:click="confirmDeleteRoom({{ $room->id }})"
                                class="px-2 py-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200">
                        <p class="text-base font-semibold text-gray-800">No rooms yet</p>
                        <p class="text-sm text-gray-400 mt-1">Add a room with rows &amp; columns — seats are auto-generated.</p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- ══════════════════════════════════════════════
             TAB: INVIGILATORS
        ══════════════════════════════════════════════ --}}
        @if ($activeTab === 'invigilators')
            <div class="flex items-center justify-between mb-5">
                <p class="text-sm text-gray-500">{{ $invigilators->count() }} invigilator(s)</p>
                <button wire:click="openInvigilatorPanel"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Invigilator
                </button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    @forelse ($invigilators as $inv)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-gray-50">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-emerald-600">{{ strtoupper(substr($inv->name, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $inv->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ $inv->phone ?: ($inv->email ?: '—') }}
                                        · max {{ $inv->max_rooms }} rooms
                                        · {{ count($inv->available_dates ?? []) }} date(s)
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $inv->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $inv->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <button wire:click="openInvigilatorPanel({{ $inv->id }})"
                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="confirmDeleteInvigilator({{ $inv->id }})"
                                    class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16">
                            <p class="text-base font-semibold text-gray-800">No invigilators yet</p>
                            <p class="text-sm text-gray-400 mt-1">Add invigilators with available dates &amp; max-room limits.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         PLAN VIEWER (room-wise charts)
    ══════════════════════════════════════════════════ --}}
    @if ($viewingPlan)
        <div class="fixed inset-0 z-40 overflow-hidden">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="closePlanView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-4xl bg-gray-50 shadow-2xl flex flex-col">

                <button wire:click="closePlanView"
                    class="absolute top-4 right-4 z-20 w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="px-6 pt-6 pb-4 bg-white border-b border-gray-200 flex-shrink-0">
                    <h2 class="text-lg font-bold text-gray-900">{{ $viewingPlan->name }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $viewingPlan->exam->exam_name ?? '' }} · {{ $viewingPlan->exam_date?->format('d M Y') }}{{ $viewingPlan->session ? ' · ' . ucfirst($viewingPlan->session) : '' }}
                    </p>
                    <div class="flex items-center gap-2 mt-3">
                        <a href="{{ route('admin.seating-plan.print', $viewingPlan->id) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-gray-900 text-white rounded-md hover:bg-gray-800">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Print / PDF
                        </a>
                        @if ($viewingPlan->conflict_count > 0)
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-100">{{ $viewingPlan->conflict_count }} conflicts</span>
                        @else
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">No conflicts</span>
                        @endif
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    @foreach ($planRooms as $room)
                        @php
                            $roomAssignments = $planAssignments->where('room_id', $room->id);
                            $cells = [];
                            foreach ($roomAssignments as $a) {
                                if ($a->seat) $cells[$a->seat->row_no][$a->seat->col_no] = $a;
                            }
                            $roomInvs = $planInvigilators->where('room_id', $room->id);
                            $filled = $roomAssignments->whereNotNull('student_id')->count();
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">{{ $room->room_name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $room->building }} · {{ $filled }}/{{ $room->capacity }} seats filled</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400">Invigilator(s)</p>
                                    <p class="text-xs font-medium text-gray-700">
                                        {{ $roomInvs->map(fn($i) => $i->invigilator->name ?? '')->filter()->implode(', ') ?: '— not assigned —' }}
                                    </p>
                                </div>
                            </div>
                            <div class="p-4 overflow-x-auto">
                                <p class="text-[10px] uppercase tracking-wide text-gray-400 text-center mb-2">⬆ Front (Board)</p>
                                <div class="inline-block min-w-full">
                                    @for ($r = 1; $r <= $room->rows; $r++)
                                        <div class="flex gap-1.5 justify-center mb-1.5">
                                            @for ($c = 1; $c <= $room->columns; $c++)
                                                @php $cell = $cells[$r][$c] ?? null; @endphp
                                                <div class="w-16 h-14 rounded-md border text-[10px] flex flex-col items-center justify-center text-center px-1 leading-tight
                                                    {{ !$cell || !$cell->student_id ? 'bg-gray-50 border-dashed border-gray-200 text-gray-300'
                                                        : ($cell->has_conflict ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200') }}">
                                                    @if ($cell && $cell->student_id)
                                                        <span class="font-bold text-gray-700">{{ $cell->seat->seat_number ?? '' }}</span>
                                                        <span class="truncate w-full text-gray-600">{{ \Illuminate\Support\Str::limit($cell->student->name ?? '', 8) }}</span>
                                                        <span class="text-[9px] text-blue-600 font-medium">{{ $cell->class_label }}</span>
                                                    @else
                                                        <span>{{ $cell->seat->seat_number ?? '' }}</span>
                                                        <span class="text-[9px]">empty</span>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         ROOM SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showRoomPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeRoomPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editRoomId ? 'Edit Room' : 'Add Room' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Seats are auto-generated from rows × columns.</p>
                    </div>
                    <button wire:click="closeRoomPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Room Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="roomForm.room_name" placeholder="e.g. Hall A"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('roomForm.room_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Building <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" wire:model="roomForm.building" placeholder="e.g. Main Block"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Rows <span class="text-red-500">*</span></label>
                            <input type="number" min="1" max="50" wire:model.live="roomForm.rows"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('roomForm.rows')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Columns <span class="text-red-500">*</span></label>
                            <input type="number" min="1" max="50" wire:model.live="roomForm.columns"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('roomForm.columns')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-sm text-blue-700">
                        Capacity: <strong>{{ (int) ($roomForm['rows'] ?? 0) * (int) ($roomForm['columns'] ?? 0) }} seats</strong>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="roomForm.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Active (available for seating plans)
                    </label>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <textarea wire:model="roomForm.notes" rows="2" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeRoomPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveRoom" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editRoomId ? 'Update' : 'Add Room' }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         INVIGILATOR SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showInvigilatorPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeInvigilatorPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editInvigilatorId ? 'Edit Invigilator' : 'Add Invigilator' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Set availability dates &amp; max rooms for fair duty distribution.</p>
                    </div>
                    <button wire:click="closeInvigilatorPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="invigilatorForm.name" placeholder="e.g. Mr. Verma"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('invigilatorForm.name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input type="email" wire:model="invigilatorForm.email"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('invigilatorForm.email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                            <input type="text" wire:model="invigilatorForm.phone"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Available Dates <span class="text-gray-400 font-normal">(comma-separated YYYY-MM-DD)</span></label>
                        <textarea wire:model="invigilatorForm.available_dates_csv" rows="2" placeholder="2026-06-01, 2026-06-02, 2026-06-05"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Rooms <span class="text-red-500">*</span></label>
                        <input type="number" min="1" max="20" wire:model="invigilatorForm.max_rooms"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('invigilatorForm.max_rooms')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="invigilatorForm.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Active
                    </label>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <textarea wire:model="invigilatorForm.notes" rows="2" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeInvigilatorPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveInvigilator" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editInvigilatorId ? 'Update' : 'Add' }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         GENERATE PLAN SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showGeneratePanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeGeneratePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Generate Seating Plan</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Algorithm interleaves classes so adjacent students differ; invigilators auto-assigned by date.</p>
                    </div>
                    <button wire:click="closeGeneratePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam <span class="text-red-500">*</span></label>
                        <select wire:model="generateForm.exam_id" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select exam…</option>
                            @foreach ($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->exam_name }} ({{ $exam->academic_year }})</option>
                            @endforeach
                        </select>
                        @error('generateForm.exam_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Plan Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="generateForm.name" placeholder="e.g. Maths Paper — 01 Jun"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('generateForm.name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="generateForm.exam_date"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('generateForm.exam_date')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Session</label>
                            <select wire:model="generateForm.session" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">—</option>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Classes <span class="text-red-500">*</span></label>
                        <div class="border border-gray-200 rounded-md p-3 max-h-40 overflow-y-auto space-y-1.5">
                            @forelse ($standards as $std)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" value="{{ $std->id }}" wire:model="generateForm.standard_ids" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    {{ $std->name }}
                                </label>
                            @empty
                                <p class="text-xs text-gray-400">No classes found.</p>
                            @endforelse
                        </div>
                        @error('generateForm.standard_ids')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Rooms <span class="text-red-500">*</span></label>
                        <div class="border border-gray-200 rounded-md p-3 max-h-40 overflow-y-auto space-y-1.5">
                            @forelse ($rooms->where('is_active', true) as $room)
                                <label class="flex items-center justify-between gap-2 text-sm text-gray-700">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" value="{{ $room->id }}" wire:model="generateForm.room_ids" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        {{ $room->room_name }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $room->capacity }} seats</span>
                                </label>
                            @empty
                                <p class="text-xs text-gray-400">No active rooms. Add rooms first.</p>
                            @endforelse
                        </div>
                        @error('generateForm.room_ids')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeGeneratePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="generatePlan" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="generatePlan">Generate Plan</span>
                        <span wire:loading wire:target="generatePlan">Generating…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRMS
    ══════════════════════════════════════════════════ --}}
    @php
        $deletes = [
            ['flag' => $pendingDeleteRoomId,        'cancel' => 'cancelDeleteRoom',        'exec' => 'executeDeleteRoom',        'title' => 'Delete room?',        'body' => 'All its seats will be removed. Existing plans keep their snapshot.'],
            ['flag' => $pendingDeleteInvigilatorId, 'cancel' => 'cancelDeleteInvigilator', 'exec' => 'executeDeleteInvigilator', 'title' => 'Delete invigilator?', 'body' => 'They will be removed from future assignments.'],
            ['flag' => $pendingDeletePlanId,        'cancel' => 'cancelDeletePlan',        'exec' => 'executeDeletePlan',        'title' => 'Delete seating plan?', 'body' => 'All seat &amp; invigilator assignments for this plan will be permanently removed.'],
        ];
    @endphp
    @foreach ($deletes as $d)
        @if ($d['flag'] !== null)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="{{ $d['cancel'] }}"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900 mb-1">{{ $d['title'] }}</h3>
                            <p class="text-sm text-gray-500">{!! $d['body'] !!}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="{{ $d['cancel'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="{{ $d['exec'] }}" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
