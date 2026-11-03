<div class="min-h-screen bg-gray-50">
    @php($current = $sessions->firstWhere('is_current', true))

    {{-- ══════════ HEADER — as Documents: title, the current session and the
         count, and Add Session on one row ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Session</h1>
                    @if ($current)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            Current: {{ $current->name }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3">
                        <span>Total: <strong class="text-gray-800">{{ $sessions->count() }}</strong></span>
                    </div>

                    <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Session</span>
                        <span class="sm:hidden">New</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        {{-- ══════════ LISTING — newest first: the session, its dates, where it
             stands today, and which one is current ══════════ --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] table-fixed">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[26%]">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Current Session</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($sessions as $i => $session)
                            @php($status = $session->status())
                            <tr class="{{ $session->is_current ? 'bg-blue-50/40' : 'hover:bg-gray-50/70' }} transition-colors" wire:key="session-{{ $session->id }}">
                                <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-10 h-10 rounded-lg {{ $session->is_current ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center flex-shrink-0">
                                            <x-icon name="calendar-days" class="w-5 h-5" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $session->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $session->months() }} {{ $session->months() === 1 ? 'month' : 'months' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $session->starts_on->format('d M Y') }} – {{ $session->ends_on->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @switch($status)
                                        @case('running')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Running</span>
                                            @break
                                        @case('upcoming')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Upcoming</span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Completed</span>
                                    @endswitch
                                </td>
                                <td class="px-4 py-3">
                                    @if ($session->is_current)
                                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Current
                                        </span>
                                    @else
                                        <button wire:click="setCurrent({{ $session->id }})" wire:loading.attr="disabled"
                                            class="text-xs font-medium px-2.5 py-1 rounded-md border border-gray-200 text-gray-600 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50 transition-colors">
                                            Set as current
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="edit({{ $session->id }})" title="Edit"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                        <button wire:click="confirmDelete({{ $session->id }})" title="{{ $session->is_current ? 'The current session can’t be deleted' : 'Delete' }}"
                                            class="p-1.5 rounded-lg transition-colors {{ $session->is_current ? 'text-gray-300 cursor-not-allowed' : 'text-red-600 hover:bg-red-50' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
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
        <p class="mt-3 text-xs text-gray-400">The current session is the one every panel's top bar shows.</p>
    </div>

    {{-- ══════════ ADD / EDIT SLIDE-IN PANEL ══════════ --}}
    @if ($showPanel)
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

                        <label class="flex items-start gap-3 p-3.5 rounded-lg border {{ $makeCurrent ? 'border-blue-200 bg-blue-50/60' : 'border-gray-200' }} cursor-pointer transition-colors">
                            <input type="checkbox" wire:model.live="makeCurrent"
                                class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-medium text-gray-800">Make this the current session</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Every panel's top bar shows the current session.</span>
                            </span>
                        </label>
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
