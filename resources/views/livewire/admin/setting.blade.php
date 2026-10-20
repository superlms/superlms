<div class="min-h-screen bg-gray-50">

    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-30">
        <div class="flex items-center gap-3 min-w-0">
            <x-admin.back-to-more />
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Setting</h1>
        </div>
    </div>

    <div class="p-3 sm:p-4 md:p-6 space-y-4">
        @if ($biometricOn)
            {{-- ══════ Biometric attendance: the school's times ══════
                 Students punch once — by the last time they are Present, after
                 it Absent. Teachers punch in and out; their times are kept, and
                 said late / early against these when they are set. --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden max-w-3xl">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">Biometric Attendance</h2>
                    <p class="text-xs text-gray-500 mt-0.5">A punch on the school's machines — fingerprint or card — marks the day as it happens.</p>
                </div>

                <div class="divide-y divide-gray-100">
                    <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                        <div class="sm:col-span-2">
                            <p class="text-sm font-medium text-gray-800">Students — last punch time</p>
                            <p class="text-xs text-gray-500 mt-0.5">A student punching by this time is <strong class="text-emerald-700">Present</strong>; after it, <strong class="text-red-600">Absent</strong>. Students have no punch-in or punch-out — one punch a day.</p>
                        </div>
                        <div>
                            <input type="time" wire:model="studentLastTime"
                                class="w-full text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('studentLastTime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                        <div class="sm:col-span-2">
                            <p class="text-sm font-medium text-gray-800">Teachers — punch-in by</p>
                            <p class="text-xs text-gray-500 mt-0.5">A teacher's first punch of the day is their punch-in (fingerprint or card). After this time it is noted as late. Leave empty to only keep the time.</p>
                        </div>
                        <div>
                            <input type="time" wire:model="teacherInTime"
                                class="w-full text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('teacherInTime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                        <div class="sm:col-span-2">
                            <p class="text-sm font-medium text-gray-800">Teachers — punch-out from</p>
                            <p class="text-xs text-gray-500 mt-0.5">The last punch of the day is the punch-out. Before this time it is noted as early.</p>
                        </div>
                        <div>
                            <input type="time" wire:model="teacherOutTime"
                                class="w-full text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('teacherOutTime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- The school's machines, as the super admin added them --}}
                    <div class="px-5 py-4">
                        <p class="text-sm font-medium text-gray-800 mb-2">Machines</p>
                        @forelse ($devices as $dev)
                            <div wire:key="set-dev-{{ $dev->id }}" class="flex items-center gap-2.5 py-1.5 text-sm">
                                <span class="w-2 h-2 rounded-full {{ $dev->online() ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                                <span class="text-gray-800">{{ $dev->name ?: 'Machine' }}</span>
                                <span class="text-xs text-gray-400 font-mono">{{ $dev->serial_number }}</span>
                                <span class="ml-auto text-xs text-gray-400">{{ $dev->last_seen_at ? 'Last heard ' . $dev->last_seen_at->diffForHumans() : 'Not connected yet' }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">No machine added yet — SuperLMS adds them for the school.</p>
                        @endforelse
                    </div>
                </div>

                <div class="px-5 py-3 border-t border-gray-200 flex justify-end">
                    <button type="button" wire:click="saveBiometric" wire:loading.attr="disabled" wire:target="saveBiometric"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveBiometric">Save</span>
                        <span wire:loading wire:target="saveBiometric">Saving…</span>
                    </button>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 px-8 py-16 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-icon name="cog-6-tooth" class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-base font-semibold text-gray-700 mb-1">Nothing here yet</h3>
                <p class="text-gray-400 text-sm">Settings will be added soon.</p>
            </div>
        @endif
    </div>
</div>
