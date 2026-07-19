<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30 px-4 sm:px-6 py-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Push Notification</h1>
            <p class="text-xs text-gray-400 mt-0.5">Reaches recipients on both the mobile app and the web dashboard.</p>
        </div>
    </div>

    <div class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ══════════════════════════════════════════════════
             COMPOSE FORM
        ══════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
            <h2 class="text-sm font-bold text-gray-900 mb-4">Compose</h2>

            <div class="space-y-4">
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
                        <div class="flex flex-wrap gap-2 mb-3">
                            <button type="button" wire:click="$set('audienceScope', 'all')"
                                class="px-3 py-1.5 text-xs font-semibold rounded-full border transition-colors
                                       {{ $audienceScope === 'all' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                All Schools
                            </button>
                            <button type="button" wire:click="$set('audienceScope', 'organization')"
                                class="px-3 py-1.5 text-xs font-semibold rounded-full border transition-colors
                                       {{ $audienceScope === 'organization' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                Specific School
                            </button>
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

                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Recipient types <span class="normal-case font-normal text-gray-400">(select one or more)</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['students' => 'Students', 'teachers' => 'Teachers', 'admins' => 'Admins'] as $role => $label)
                            @php $on = in_array($role, $audienceRoles, true); @endphp
                            <button type="button" wire:click="toggleRole('{{ $role }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border transition-colors
                                       {{ $on ? 'bg-violet-600 border-violet-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                <span class="w-3.5 h-3.5 rounded-[4px] border flex items-center justify-center
                                             {{ $on ? 'bg-white/20 border-white/60' : 'border-gray-300' }}">
                                    @if ($on)
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    @endif
                                </span>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    @error('audienceRoles') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button wire:click="review" wire:loading.attr="disabled" wire:target="review"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        Review &amp; Send
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             SIDE NOTE
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 h-fit">
            <h2 class="text-sm font-bold text-gray-900 mb-3">How this works</h2>
            <ul class="space-y-2.5 text-xs text-gray-500">
                <li class="flex gap-2"><span class="text-blue-500">•</span> Delivered <strong class="text-gray-700">two ways</strong>: a push to every registered app device, and an in-app notification in the recipient's web dashboard bell.</li>
                <li class="flex gap-2"><span class="text-blue-500">•</span> Target students, teachers and school admins — pick any combination.</li>
                <li class="flex gap-2"><span class="text-blue-500">•</span> App delivery only reaches users who opened the app and allowed notifications; the web inbox reaches everyone.</li>
                <li class="flex gap-2"><span class="text-blue-500">•</span> You'll see the exact recipient and device count before it actually sends.</li>
            </ul>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         CAMPAIGN HISTORY
    ══════════════════════════════════════════════════ --}}
    <div class="px-4 sm:px-6 pb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Recent Campaigns</h2>
            </div>

            @if ($campaigns->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                                <th class="px-5 py-2.5">Title</th>
                                <th class="px-5 py-2.5">Audience</th>
                                <th class="px-5 py-2.5">Recipients</th>
                                <th class="px-5 py-2.5">Devices</th>
                                <th class="px-5 py-2.5">Status</th>
                                <th class="px-5 py-2.5">Sent</th>
                                <th class="px-5 py-2.5 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($campaigns as $c)
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-gray-800">{{ $c->title }}</p>
                                        <p class="text-xs text-gray-400 truncate max-w-xs">{{ $c->body }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-600">
                                        {{ $c->organization?->name ?? 'All Schools' }}
                                        <span class="text-gray-400">· {{ $c->audienceRolesLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->recipient_count) }}</td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->device_count) }}</td>
                                    <td class="px-5 py-3">
                                        @if ($c->delivered)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 text-[11px] font-medium">Delivered</span>
                                        @elseif ($c->recipient_count > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-100 text-[11px] font-medium">Web only</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-50 text-gray-500 border border-gray-200 text-[11px] font-medium">No recipients</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-400">{{ $c->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <button wire:click="viewCampaign({{ $c->id }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            View
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
         REVIEW & CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($confirming)
        @teleport('body')
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelReview"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Send this notification?</h3>
                        <p class="text-sm text-gray-500">Goes out to the app and the web dashboard immediately and can't be recalled.</p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 mb-5 space-y-2">
                    <p class="text-sm font-semibold text-gray-800">{{ $title }}</p>
                    <p class="text-xs text-gray-500">{{ $body }}</p>
                    <div class="flex items-center gap-3 pt-2 text-xs text-gray-600">
                        <span><strong class="text-gray-900">{{ number_format($previewRecipients ?? 0) }}</strong> recipients</span>
                        <span class="text-gray-300">|</span>
                        <span><strong class="text-gray-900">{{ number_format($previewDevices ?? 0) }}</strong> app devices</span>
                    </div>
                    <p class="text-[11px] text-gray-400">All {{ number_format($previewRecipients ?? 0) }} recipients also get it in their web notification bell.</p>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button wire:click="cancelReview" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="send" wire:loading.attr="disabled" wire:target="send"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="send">Confirm &amp; Send</span>
                        <span wire:loading wire:target="send">Sending…</span>
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         CAMPAIGN DETAIL OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($viewing)
        @teleport('body')
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="closeView"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 border-b border-gray-100">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-violet-50 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 leading-tight">{{ $viewing->title }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Sent {{ $viewing->created_at->format('d M Y, h:i A') }}
                                @if ($viewing->sender) · by {{ $viewing->sender->name }} @endif
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">
                    {{-- Message --}}
                    <div>
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Message</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $viewing->body }}</p>
                    </div>

                    {{-- Reach cards --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 text-center">
                            <p class="text-lg font-bold text-gray-900">{{ number_format($viewing->recipient_count) }}</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Recipients</p>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-center">
                            <p class="text-lg font-bold text-blue-700">{{ number_format($viewing->device_count) }}</p>
                            <p class="text-[11px] text-blue-600/80 mt-0.5">App devices</p>
                        </div>
                        <div class="rounded-xl border border-violet-100 bg-violet-50 p-3 text-center">
                            <p class="text-lg font-bold text-violet-700">{{ number_format($viewing->web_count) }}</p>
                            <p class="text-[11px] text-violet-600/80 mt-0.5">Web inboxes</p>
                        </div>
                    </div>

                    {{-- Device breakdown --}}
                    @php $breakdown = $viewing->device_breakdown ?: []; @endphp
                    @if (!empty($breakdown))
                        <div>
                            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Devices reached by platform</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($breakdown as $platform => $count)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-200 text-xs text-gray-700">
                                        <span class="font-semibold capitalize">{{ $platform }}</span>
                                        <span class="text-gray-400">·</span>
                                        <span>{{ number_format($count) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Meta --}}
                    <div class="grid grid-cols-2 gap-x-4 gap-y-3 pt-1">
                        <div>
                            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">School</p>
                            <p class="text-sm text-gray-700">{{ $viewing->organization?->name ?? 'All Schools' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Audience</p>
                            <p class="text-sm text-gray-700">{{ $viewing->audienceRolesLabel() }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Deep-link screen</p>
                            <p class="text-sm text-gray-700">{{ $viewing->screen ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">App delivery</p>
                            <p class="text-sm {{ $viewing->delivered ? 'text-emerald-600' : 'text-amber-600' }} font-medium">
                                {{ $viewing->delivered ? 'Delivered to devices' : 'No reachable devices' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                    <button wire:click="closeView" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Close</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

</div>
