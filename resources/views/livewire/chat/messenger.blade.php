<div class="h-[calc(100vh-4rem)] bg-gray-50" wire:poll.4s>
    <div class="h-full flex">

        {{-- ══════════════════════════════════════════════════
             LEFT — Contacts / threads
        ══════════════════════════════════════════════════ --}}
        <div class="w-full sm:w-80 md:w-96 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col
                    {{ $selectedUserId ? 'hidden sm:flex' : 'flex' }}">
            <div class="px-4 py-4 border-b border-gray-100">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-gray-900">Messages</h1>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $panelLabel }}</p>
                    </div>
                    <button wire:click="toggleSelectMode"
                        class="flex-shrink-0 text-xs font-medium px-2.5 py-1.5 rounded-lg border transition-colors
                               {{ $selectMode ? 'border-gray-300 text-gray-600 bg-gray-50 hover:bg-gray-100' : 'border-blue-200 text-blue-600 hover:bg-blue-50' }}">
                        {{ $selectMode ? 'Cancel' : 'Select' }}
                    </button>
                </div>

                @if ($selectMode)
                    <div class="mt-3 flex items-center justify-between gap-2 bg-rose-50/60 border border-rose-100 rounded-lg px-3 py-2">
                        <span class="text-xs font-medium text-gray-600">{{ count($selectedThreads) }} selected</span>
                        <div class="flex items-center gap-3">
                            <button wire:click="selectAllThreads" class="text-xs font-medium text-blue-600 hover:text-blue-700">Select all</button>
                            <button wire:click="confirmDelete" @disabled(empty($selectedThreads))
                                class="text-xs font-semibold text-rose-600 hover:text-rose-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                Delete
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mt-3 relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="contactSearch" type="text" placeholder="Search people..."
                            class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400">
                    </div>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto">
                @forelse ($contacts as $c)
                    @php
                        $u        = $c['user'];
                        $cid      = $c['conversation_id'];
                        $selected = $cid && in_array($cid, $selectedThreads);
                    @endphp
                    <div wire:key="contact-{{ $u->id }}" class="relative group border-b border-gray-50">
                        <button
                            @if ($selectMode)
                                @if ($cid) wire:click="toggleThread({{ $cid }})" @else disabled @endif
                            @else
                                wire:click="openChat({{ $u->id }})"
                            @endif
                            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 transition-colors
                                   {{ !$selectMode && $selectedUserId === $u->id ? 'bg-blue-50/60' : '' }}
                                   {{ $selected ? 'bg-rose-50/60' : '' }}
                                   {{ $selectMode && !$cid ? 'opacity-40 cursor-not-allowed' : '' }}">

                            @if ($selectMode)
                                <span class="flex-shrink-0 w-5 h-5 rounded-md border flex items-center justify-center transition-colors
                                             {{ $selected ? 'bg-rose-500 border-rose-500 text-white' : 'border-gray-300 bg-white' }}">
                                    @if ($selected)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    @endif
                                </span>
                            @endif

                            <div class="relative flex-shrink-0">
                                @if ($u->image)
                                    <img src="{{ $u->image }}" class="w-11 h-11 rounded-full object-cover border border-gray-200" alt="">
                                @else
                                    <span class="w-11 h-11 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                @endif
                                @if (!$selectMode && $c['unread'] > 0)
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $c['unread'] > 99 ? '99+' : $c['unread'] }}</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-medium text-gray-800 truncate">{{ $u->name }}</p>
                                    @if ($c['last'])
                                        <span class="text-[11px] text-gray-400 flex-shrink-0">{{ $c['last']->created_at->format('h:i A') }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 truncate">
                                    @if ($c['last'])
                                        @if ($c['last']->body)
                                            {{ $c['last']->body }}
                                        @elseif ($c['last']->attachment_type === 'image')
                                            📷 Photo
                                        @elseif ($c['last']->attachment_type)
                                            📎 {{ $c['last']->attachment_name }}
                                        @endif
                                    @else
                                        {{ $c['role_label'] }} · Tap to start chat
                                    @endif
                                </p>
                            </div>
                        </button>

                        @if (!$selectMode && $cid)
                            <button wire:click="confirmDeleteThread({{ $cid }})" title="Delete chat"
                                class="absolute right-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity
                                       p-1.5 rounded-md text-gray-400 hover:text-rose-600 hover:bg-rose-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-12 text-center text-sm text-gray-400">No one to chat with yet.</div>
                @endforelse
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             RIGHT — Conversation
        ══════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-w-0 {{ $selectedUserId ? 'flex' : 'hidden sm:flex' }}">
            @if ($otherUser)
                {{-- Header --}}
                <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 flex-shrink-0">
                    <button wire:click="$set('selectedUserId', null)" class="sm:hidden p-1.5 -ml-1 rounded-md text-gray-500 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    @if ($otherUser->image)
                        <img src="{{ $otherUser->image }}" class="w-10 h-10 rounded-full object-cover border border-gray-200" alt="">
                    @else
                        <span class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold">{{ strtoupper(substr($otherUser->name, 0, 1)) }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $otherUser->name }}</p>
                        <p class="text-xs text-gray-500">{{ $otherRoleLabel }}</p>
                    </div>
                    @if ($conversationId)
                        <button wire:click="confirmDeleteThread({{ $conversationId }})" title="Delete chat"
                            class="ml-auto p-2 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    @endif
                </div>

                {{-- Messages --}}
                <div id="chatScroll" class="flex-1 overflow-y-auto px-4 py-4 space-y-2 bg-gray-50">
                    @forelse ($messages as $m)
                        @php $mine = $m->sender_id === $myId; @endphp
                        <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" wire:key="msg-{{ $m->id }}">
                            <div class="max-w-[78%] sm:max-w-[65%] rounded-2xl px-3.5 py-2 shadow-sm
                                        {{ $mine ? 'bg-blue-600 text-white rounded-br-md' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-md' }}">
                                @if ($m->attachment_type === 'image' && $m->attachment_url)
                                    <a href="{{ $m->attachment_url }}" target="_blank">
                                        <img src="{{ $m->attachment_url }}" class="rounded-lg max-h-60 w-auto mb-1 border {{ $mine ? 'border-blue-400' : 'border-gray-200' }}" alt="attachment">
                                    </a>
                                @elseif ($m->attachment_type && $m->attachment_url)
                                    <a href="{{ $m->attachment_url }}" target="_blank"
                                       class="flex items-center gap-2 mb-1 px-2 py-1.5 rounded-lg {{ $mine ? 'bg-blue-500/40' : 'bg-gray-50 border border-gray-200' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 {{ $mine ? 'text-white' : 'text-blue-600' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        <span class="text-xs truncate max-w-[160px] {{ $mine ? 'text-white' : 'text-gray-700' }}">{{ $m->attachment_name ?: 'Document' }}</span>
                                    </a>
                                @endif

                                @if ($m->body)
                                    <p class="text-sm whitespace-pre-line break-words">{{ $m->body }}</p>
                                @endif

                                <div class="flex items-center justify-end gap-1 mt-0.5">
                                    <span class="text-[10px] {{ $mine ? 'text-blue-100' : 'text-gray-400' }}">{{ $m->created_at->format('h:i A') }}</span>
                                    @if ($mine)
                                        @if ($m->read_at)
                                            <svg class="w-4 h-4 text-sky-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" title="Read"><path stroke-linecap="round" stroke-linejoin="round" d="M1 13l4 4L13 7" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 13l4 4L23 7" /></svg>
                                        @else
                                            <svg class="w-3.5 h-3.5 text-blue-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" title="Sent"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex items-center justify-center text-sm text-gray-400">No messages yet. Say hello 👋</div>
                    @endforelse
                </div>

                {{-- Composer --}}
                <div class="bg-white border-t border-gray-200 px-3 py-3 flex-shrink-0">
                    @error('body') <p class="text-xs text-rose-500 mb-1.5 px-1">{{ $message }}</p> @enderror
                    @error('attachment') <p class="text-xs text-rose-500 mb-1.5 px-1">{{ $message }}</p> @enderror

                    @if ($attachment)
                        <div class="flex items-center gap-2 mb-2 px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            <span class="truncate flex-1">{{ $attachment->getClientOriginalName() }}</span>
                            <button type="button" wire:click="$set('attachment', null)" class="text-rose-500 hover:text-rose-700">Remove</button>
                        </div>
                    @endif
                    <div wire:loading wire:target="attachment" class="text-xs text-blue-600 mb-1.5 px-1">Uploading attachment…</div>

                    <form x-data="{ draft: '' }"
                          @submit.prevent="if (draft.trim() !== '' || $wire.attachment) { $wire.sendMessage(draft).then(() => draft = '') }"
                          class="flex items-end gap-2">
                        <label class="flex-shrink-0 w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50 cursor-pointer" title="Attach image or document">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            <input type="file" wire:model="attachment" class="hidden" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                        </label>

                        <textarea x-model="draft" rows="1" placeholder="Type a message..."
                            @keydown.enter.exact.prevent="if (draft.trim() !== '' || $wire.attachment) { $wire.sendMessage(draft).then(() => draft = '') }"
                            class="flex-1 resize-none border border-gray-300 rounded-2xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 max-h-32"></textarea>

                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                            class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </button>
                    </form>
                </div>
            @else
                {{-- Empty state --}}
                <div class="flex-1 flex flex-col items-center justify-center text-center px-6">
                    <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.83L3 20l1.4-3.5A7.9 7.9 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800">Your messages</h3>
                    <p class="text-sm text-gray-400 mt-1 max-w-xs">Select a person on the left to start a conversation. Messages update live.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         Delete confirmation modal
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        @php $count = count($selectedThreads); @endphp
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/30 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-rose-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Delete {{ $count > 1 ? $count . ' chats' : 'chat' }}?</h3>
                        <p class="text-xs text-gray-400">This cannot be undone</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-5">
                    The selected {{ $count > 1 ? 'conversations' : 'conversation' }} and all their messages will be
                    <strong class="text-gray-700">permanently deleted</strong> from the database for everyone.
                </p>
                <div class="flex items-center gap-2">
                    <button wire:click="deleteSelected"
                        class="flex-1 py-2 bg-rose-500 hover:bg-rose-600 text-white text-sm font-medium rounded-lg transition-colors">
                        Yes, delete
                    </button>
                    <button wire:click="$set('showDeleteConfirm', false)"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Auto-scroll the open thread to the latest message --}}
    @script
    <script>
        const toBottom = () => {
            const el = document.getElementById('chatScroll');
            if (el) el.scrollTop = el.scrollHeight;
        };
        toBottom();
        Livewire.hook('morph.updated', () => setTimeout(toBottom, 30));
    </script>
    @endscript
</div>
