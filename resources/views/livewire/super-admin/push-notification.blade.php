<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30 px-4 sm:px-6 py-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Push Notification</h1>
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

                    <div class="flex flex-wrap gap-2">
                        @foreach (['both' => 'Students & Teachers', 'students' => 'Students only', 'teachers' => 'Teachers only'] as $role => $label)
                            <button type="button" wire:click="$set('audienceRole', '{{ $role }}')"
                                class="px-3 py-1.5 text-xs font-semibold rounded-full border transition-colors
                                       {{ $audienceRole === $role ? 'bg-violet-600 border-violet-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
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
                <li class="flex gap-2"><span class="text-blue-500">•</span> Delivered as a push notification to every registered device for the matched audience.</li>
                <li class="flex gap-2"><span class="text-blue-500">•</span> Only users who have opened the app and granted notification permission can be reached.</li>
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
                                        <span class="text-gray-400">· {{ ucfirst($c->audience_role) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->recipient_count) }}</td>
                                    <td class="px-5 py-3 text-gray-700">{{ number_format($c->device_count) }}</td>
                                    <td class="px-5 py-3">
                                        @if ($c->delivered)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 text-[11px] font-medium">Delivered</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-50 text-gray-500 border border-gray-200 text-[11px] font-medium">No devices</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-400">{{ $c->created_at->format('d M Y, h:i A') }}</td>
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
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelReview"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Send this notification?</h3>
                        <p class="text-sm text-gray-500">This goes out immediately and can't be recalled.</p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 mb-5 space-y-2">
                    <p class="text-sm font-semibold text-gray-800">{{ $title }}</p>
                    <p class="text-xs text-gray-500">{{ $body }}</p>
                    <div class="flex items-center gap-3 pt-2 text-xs text-gray-600">
                        <span><strong class="text-gray-900">{{ number_format($previewRecipients ?? 0) }}</strong> recipients</span>
                        <span class="text-gray-300">|</span>
                        <span><strong class="text-gray-900">{{ number_format($previewDevices ?? 0) }}</strong> devices</span>
                    </div>
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
    @endif

</div>
