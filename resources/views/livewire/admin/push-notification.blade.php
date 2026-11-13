<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER — Add Notification opens the compose slide-in ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30 px-4 sm:px-6 py-3">
        <div class="flex items-center justify-between gap-3 sm:min-h-9">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Push Notification</h1>
            </div>
            <button wire:click="openCompose"
                class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span class="hidden sm:inline">Add Notification</span>
                <span class="sm:hidden">Add</span>
            </button>
        </div>
    </div>

    {{-- ══════════ SENT — every notification the school sent ══════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Sent Notifications</h2>
            </div>

            @if ($sent->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                                <th class="px-5 py-2.5 w-72">Title</th>
                                <th class="px-5 py-2.5">Sent To</th>
                                <th class="px-5 py-2.5">Recipients</th>
                                <th class="px-5 py-2.5">Devices</th>
                                <th class="px-5 py-2.5">Status</th>
                                <th class="px-5 py-2.5">Sent</th>
                                <th class="px-5 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($sent as $c)
                                <tr wire:key="push-{{ $c->id }}">
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-gray-800">{{ $c->title }}</p>
                                        <p class="text-xs text-gray-400 truncate max-w-[15rem]">{{ $c->body }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-xs">
                                        <p class="text-gray-600 max-w-[14rem] truncate">{{ $c->audience_label ?: (\App\Models\SchoolPushNotification::AUDIENCES[$c->audience] ?? '—') }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->recipient_count) }}</td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->device_count) }}</td>
                                    <td class="px-5 py-3 text-xs font-medium whitespace-nowrap">
                                        @if ($c->delivered)
                                            <span class="text-emerald-600">Delivered</span>
                                        @else
                                            <span class="text-amber-600">Bell only</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $c->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        <button wire:click="viewCampaign({{ $c->id }})" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $c->id }})" title="Delete"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $sent->links() }}
                </div>
            @else
                <p class="text-sm text-gray-400 py-10 text-center">No notifications sent yet</p>
            @endif
        </div>
    </div>

    {{-- ══════════ COMPOSE SLIDE-IN ══════════ --}}
    @if ($composing)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCompose"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Add Notification</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Goes to the app and the notification bell</p>
                    </div>
                    <button wire:click="closeCompose" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Title</label>
                        <input type="text" wire:model="title" maxlength="100" placeholder="e.g. School closed tomorrow"
                            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Message</label>
                        <textarea wire:model="body" maxlength="500" rows="4" placeholder="What do you want to tell them?"
                            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Who should receive this?</label>
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-3">
                            @foreach (\App\Models\SchoolPushNotification::AUDIENCES as $key => $label)
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer" wire:key="push-aud-{{ $key }}">
                                    <input type="radio" wire:model.live="audience" value="{{ $key }}" @checked($audience === $key)
                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>

                        @if ($audience === 'students')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Class</label>
                                    <select wire:model.live="standardId"
                                        class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All classes</option>
                                        @foreach ($standards as $std)
                                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('standardId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Section</label>
                                    <select wire:model.live="sectionId" @disabled($standardId === '')
                                        class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 disabled:bg-gray-50 disabled:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All sections</option>
                                        @foreach ($sections as $sec)
                                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('sectionId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @elseif ($audience === 'selected')
                            <div>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search a student or teacher by name, admission no. or phone"
                                        class="w-full text-sm bg-white border border-gray-200 rounded-lg pl-9 pr-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                @if (mb_strlen(trim($search)) >= 2)
                                    <div class="mt-2 rounded-lg border border-gray-200 divide-y divide-gray-100 max-h-60 overflow-y-auto">
                                        @forelse ($results as $p)
                                            <button type="button" wire:click="pick('{{ $p['key'] }}')" wire:key="push-res-{{ $p['key'] }}"
                                                class="w-full flex items-center justify-between gap-3 px-3 py-2 text-left hover:bg-blue-50">
                                                <span class="min-w-0">
                                                    <span class="block text-sm text-gray-800 truncate">{{ $p['name'] }}</span>
                                                    <span class="block text-[11px] text-gray-400 truncate">{{ $p['type'] }}{{ $p['meta'] ? ' · ' . $p['meta'] : '' }}</span>
                                                </span>
                                                <span class="text-xs font-medium text-blue-600 flex-shrink-0">+ Add</span>
                                            </button>
                                        @empty
                                            <p class="px-3 py-3 text-xs text-gray-400">No student or teacher found.</p>
                                        @endforelse
                                    </div>
                                @endif

                                <p class="text-xs font-semibold text-gray-600 mt-4 mb-2">Picked <span class="font-normal text-gray-400">({{ count($pickedPeople) }})</span></p>
                                @if ($pickedPeople)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($pickedPeople as $p)
                                            <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1 py-1 rounded-full bg-blue-50 text-blue-800 text-xs" wire:key="push-pick-{{ $p['key'] }}">
                                                <span class="max-w-[12rem] truncate">{{ $p['name'] }}</span>
                                                <span class="text-blue-400">{{ $p['type'] }}</span>
                                                <button type="button" wire:click="unpick('{{ $p['key'] }}')" title="Remove"
                                                    class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-blue-100 text-blue-500">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-gray-400">Search above and add the students and teachers it should go to.</p>
                                @endif
                            </div>
                        @endif
                        @error('audience') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <h3 class="text-xs font-semibold text-gray-600 mb-2">How this works</h3>
                        <ul class="space-y-2 text-xs text-gray-500">
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>Delivered <strong class="text-gray-700">two ways</strong>: a push to the phone of everyone with the app, and in their notification bell.</span></li>
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>Only your school's students and teachers can receive it.</span></li>
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>The phone push only reaches people who have signed in to the app and allowed notifications.</span></li>
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>You'll see how many people and phones it reaches before it goes.</span></li>
                        </ul>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeCompose" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="review" wire:loading.attr="disabled" wire:target="review"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        Review &amp; Send
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ REVIEW & CONFIRM ══════════ --}}
    @if ($confirming)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="cancelReview"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Send this notification?</h2>
                        <p class="text-xs text-gray-500 mt-0.5">It goes out at once and can't be called back from phones.</p>
                    </div>
                    <button wire:click="cancelReview" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Title</span>
                        <span class="col-span-2 text-gray-800 font-medium break-words">{{ $title }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Message</span>
                        <span class="col-span-2 text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $body }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Recipients</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ number_format($previewRecipients ?? 0) }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">App devices</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ number_format($previewDevices ?? 0) }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Bell</span>
                        <span class="col-span-2 text-gray-600">All {{ number_format($previewRecipients ?? 0) }} recipients also get it in their notification bell.</span>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="cancelReview" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="send" type="button" wire:loading.attr="disabled" wire:target="send"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="send">Confirm &amp; Send</span>
                        <span wire:loading wire:target="send">Sending…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DETAIL ══════════ --}}
    @if ($viewing)
        @php
            $platforms = collect($viewing->device_breakdown ?: [])
                ->map(fn ($count, $platform) => ucfirst($platform) . ' ' . number_format($count))
                ->implode(' · ');
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">Notification Details</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $viewing->created_at->format('d M Y · g:i A') }}</p>
                    </div>
                    <button wire:click="closeView"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Title</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 break-words">{{ $viewing->title }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Message</p>
                            <p class="mt-1 text-sm text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $viewing->body }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Sent To</p>
                            <p class="mt-1 text-sm text-gray-800 break-words">{{ $viewing->audience_label ?: (\App\Models\SchoolPushNotification::AUDIENCES[$viewing->audience] ?? '—') }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Sent By</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $viewing->sender?->name ?? '—' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Reach</p>
                            <p class="mt-1 text-sm text-gray-800">
                                {{ number_format($viewing->recipient_count) }} recipients
                                <span class="mx-1 text-gray-300">·</span>{{ number_format($viewing->device_count) }} app devices
                                <span class="mx-1 text-gray-300">·</span>{{ number_format($viewing->web_count) }} bells
                            </p>
                            @if ($platforms !== '')
                                <p class="text-xs text-gray-500 mt-0.5">{{ $platforms }}</p>
                            @endif
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">App Delivery</p>
                            <p class="mt-1 text-sm font-medium {{ $viewing->delivered ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $viewing->delivered ? 'Delivered to devices' : 'No reachable devices' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button type="button" wire:click="confirmDelete({{ $viewing->id }})"
                        class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                    <button type="button" wire:click="closeView"
                        class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md transition-colors">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($deletingId !== null)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Notification</h3>
                </div>
                <div class="p-5 space-y-3">
                    <p class="text-sm text-gray-600">Are you sure you want to delete this notification? This action cannot be undone.</p>
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="deleteFromBells" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <span class="text-sm text-gray-700">Also remove it from the recipients' notification bells</span>
                    </label>
                    <p class="text-xs text-gray-400">Notifications already shown on phones stay there.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteCampaign" wire:loading.attr="disabled" wire:target="deleteCampaign"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-60">Yes, Delete</button>
                    <button wire:click="cancelDelete"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

</div>
