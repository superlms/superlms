<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER — Add Notification opens the compose slide-in
    ══════════════════════════════════════════════════ --}}
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

    {{-- ══════════════════════════════════════════════════
         CAMPAIGN HISTORY — every notification sent
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Recent Campaigns</h2>
            </div>

            @if ($campaigns->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                                <th class="px-5 py-2.5 w-72">Title</th>
                                <th class="px-5 py-2.5">Audience</th>
                                <th class="px-5 py-2.5">Recipients</th>
                                <th class="px-5 py-2.5">Devices</th>
                                <th class="px-5 py-2.5">Status</th>
                                <th class="px-5 py-2.5">Sent</th>
                                <th class="px-5 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($campaigns as $c)
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-gray-800">{{ $c->title }}</p>
                                        <p class="text-xs text-gray-400 truncate max-w-[15rem]">{{ $c->body }}</p>
                                    </td>
                                    {{-- The school on one line, who it went to under it --}}
                                    <td class="px-5 py-3 text-xs">
                                        <p class="text-gray-600">{{ $c->organization?->name ?? 'All Schools' }}</p>
                                        <p class="text-gray-400 mt-0.5 whitespace-nowrap">{{ $c->audienceRolesLabel() }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->recipient_count) }}</td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->device_count) }}</td>
                                    <td class="px-5 py-3 text-xs font-medium whitespace-nowrap">
                                        @if ($c->delivered)
                                            <span class="text-emerald-600">Delivered</span>
                                        @elseif ($c->recipient_count > 0)
                                            <span class="text-amber-600">Web only</span>
                                        @else
                                            <span class="text-gray-500">No recipients</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-400">{{ $c->created_at->format('d M Y, h:i A') }}</td>
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
                    {{ $campaigns->links() }}
                </div>
            @else
                <p class="text-sm text-gray-400 py-10 text-center">No campaigns sent yet</p>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         COMPOSE SLIDE-IN — the same form, fields and Review & Send as
         before; the notes that sat beside it are under the form.
    ══════════════════════════════════════════════════ --}}
    @if ($composing)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCompose"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Add Notification</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Goes to the app and the web dashboard</p>
                    </div>
                    <button wire:click="closeCompose" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Title</label>
                        <input type="text" wire:model="title" maxlength="100" placeholder="e.g. Summer Break Registrations Open!"
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

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Deep link screen <span class="font-normal text-gray-400">(optional)</span>
                        </label>
                        <input type="text" wire:model="screen" maxlength="100" placeholder="e.g. announcements"
                            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-[11px] text-gray-400 mt-1">If set, tapping the notification opens this screen in the app.</p>
                        @error('screen') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Who should receive this?</label>

                        @if ($orgLocked)
                            <p class="text-xs text-gray-500 mb-3">
                                Restricted to <strong class="text-gray-800">{{ $organizations->first()->name ?? 'your school' }}</strong>.
                            </p>
                        @else
                            {{-- Plain radio buttons --}}
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-3">
                                @foreach (['all' => 'All Schools', 'organization' => 'Specific School'] as $scope => $scopeLabel)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="radio" wire:model.live="audienceScope" value="{{ $scope }}" @checked($audienceScope === $scope)
                                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        {{ $scopeLabel }}
                                    </label>
                                @endforeach
                            </div>

                            @if ($audienceScope === 'organization')
                                <select wire:model="organizationId"
                                    class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 mb-3
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a school…</option>
                                    @foreach ($organizations as $org)
                                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                                    @endforeach
                                </select>
                                @error('organizationId') <p class="text-xs text-red-500 mb-3">{{ $message }}</p> @enderror
                            @endif
                        @endif

                        <p class="text-xs font-semibold text-gray-600 mb-2">Recipient types <span class="font-normal text-gray-400">(select one or more)</span></p>
                        {{-- Plain checkboxes; a tick runs toggleRole as the old chips did --}}
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                            @foreach (['students' => 'Students', 'teachers' => 'Teachers', 'admins' => 'Admins'] as $role => $label)
                                @php $on = in_array($role, $audienceRoles, true); @endphp
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer" wire:key="push-role-{{ $role }}">
                                    <input type="checkbox" wire:click="toggleRole('{{ $role }}')" @checked($on)
                                        class="w-4 h-4 rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('audienceRoles') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                    </div>

                    {{-- How this works (was the card beside the form) --}}
                    <div class="border-t border-gray-100 pt-4">
                        <h3 class="text-xs font-semibold text-gray-600 mb-2">How this works</h3>
                        <ul class="space-y-2 text-xs text-gray-500">
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>Delivered <strong class="text-gray-700">two ways</strong>: a push to every registered app device, and an in-app notification in the recipient's web dashboard bell.</span></li>
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>Target students, teachers and school admins — pick any combination.</span></li>
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>App delivery only reaches users who opened the app and allowed notifications; the web inbox reaches everyone.</span></li>
                            <li class="flex gap-2"><span class="text-blue-500">•</span><span>You'll see the exact recipient and device count before it actually sends.</span></li>
                        </ul>
                    </div>
                </div>

                {{-- Footer --}}
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

    {{-- ══════════════════════════════════════════════════
         REVIEW & CONFIRM — a slide-in over the compose panel (it was a
         popup in the middle): what goes out and to how many, as plain
         label / value rows, then Cancel and Confirm & Send. Same step,
         same figures, same send.
    ══════════════════════════════════════════════════ --}}
    @if ($confirming)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="cancelReview"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Send this notification?</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Goes out to the app and the web dashboard immediately and can't be recalled.</p>
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
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Web bell</span>
                        <span class="col-span-2 text-gray-600">All {{ number_format($previewRecipients ?? 0) }} recipients also get it in their web notification bell.</span>
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

    {{-- ══════════════════════════════════════════════════
         CAMPAIGN DETAIL — a slide-in in the Announcement view's look: one
         plain card of label / value rows (same figures as before).
    ══════════════════════════════════════════════════ --}}
    @if ($viewing)
        @php
            $breakdown = $viewing->device_breakdown ?: [];
            $platforms = collect($breakdown)
                ->map(fn ($count, $platform) => ucfirst($platform) . ' ' . number_format($count))
                ->implode(' · ');
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeView"></div>

            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">Notification Details</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewing->created_at->format('d M Y · g:i A') }}
                        </p>
                    </div>
                    <button wire:click="closeView"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Panel Body --}}
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
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Sent By</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $viewing->sender?->name ?? '—' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Sent</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $viewing->created_at->format('d M Y · g:i A') }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Audience</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $viewing->organization?->name ?? 'All Schools' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $viewing->audienceRolesLabel() }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Reach</p>
                            <p class="mt-1 text-sm text-gray-800">
                                {{ number_format($viewing->recipient_count) }} recipients
                                <span class="mx-1 text-gray-300">·</span>{{ number_format($viewing->device_count) }} app devices
                                <span class="mx-1 text-gray-300">·</span>{{ number_format($viewing->web_count) }} web inboxes
                            </p>
                            @if ($platforms !== '')
                                <p class="text-xs text-gray-500 mt-0.5">{{ $platforms }}</p>
                            @endif
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Deep-link Screen</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $viewing->screen ?: '—' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">App Delivery</p>
                            <p class="mt-1 text-sm font-medium {{ $viewing->delivered ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $viewing->delivered ? 'Delivered to devices' : 'No reachable devices' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Panel Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button type="button" wire:click="confirmDelete({{ $viewing->id }})"
                        class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">
                        Delete
                    </button>
                    <button type="button" wire:click="closeView"
                        class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM — the panel's standard delete popup; by default the
         notification also leaves the recipients' notification bells.
    ══════════════════════════════════════════════════ --}}
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
