<div class="h-[calc(100vh-4rem)] bg-white" wire:poll.4s
     x-data="{
         copy(text) {
             const done = () => window.lmsToast && window.lmsToast({ type: 'success', message: 'Copied' });
             if (navigator.clipboard && window.isSecureContext) {
                 navigator.clipboard.writeText(text).then(done).catch(() => this.fallback(text, done));
             } else {
                 this.fallback(text, done);
             }
         },
         fallback(text, done) {
             const ta = document.createElement('textarea');
             ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
             document.body.appendChild(ta); ta.select();
             try { document.execCommand('copy'); } catch (e) {}
             ta.remove(); done();
         },

         /* The per-message menu lives in one fixed-position panel teleported to
            <body>. Rendering it inside each bubble meant the scroll container
            clipped it off for messages near the top or bottom of the thread. */
         menu: { open: false, id: null, pinned: false, body: null, top: 0, left: 0 },
         openMenu(el, data) {
             const r = el.getBoundingClientRect();
             const h = data.body ? 190 : 158;
             const w = 176;

             let top = r.bottom + 6;
             if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);

             let left = r.right - w;
             if (left < 8) left = 8;
             if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;

             this.menu = Object.assign({ open: true, top: top, left: left }, data);
         },
         run(fn) { this.menu.open = false; fn(); },
     }"
     x-on:chat-copy.window="copy($event.detail.text)"
     x-on:chat-menu-close.window="menu.open = false"
     x-on:keydown.escape.window="menu.open = false">
    <div class="h-full flex">

        {{-- ══════════════════════════════════════════════════
             LEFT — Chats
        ══════════════════════════════════════════════════ --}}
        <div class="w-full sm:w-80 md:w-96 flex-shrink-0 border-r border-gray-100 flex flex-col
                    {{ $selectedUserId ? 'hidden sm:flex' : 'flex' }}">
            <div class="px-4 pt-4 pb-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h1 class="text-[17px] font-semibold text-gray-900 leading-tight">Messages</h1>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $panelLabel }}</p>
                    </div>
                    <button wire:click="toggleSelectMode"
                        class="flex-shrink-0 text-xs font-medium px-2.5 py-1.5 rounded-lg transition-colors
                               {{ $selectMode ? 'text-gray-500 hover:bg-gray-100' : 'text-blue-600 hover:bg-blue-50' }}">
                        {{ $selectMode ? 'Cancel' : 'Select' }}
                    </button>
                </div>

                @if ($selectMode)
                    <div class="mt-3 flex items-center justify-between gap-2 rounded-xl bg-gray-50 px-3 py-2">
                        <span class="text-xs text-gray-500">{{ count($selectedThreads) }} selected</span>
                        <div class="flex items-center gap-3">
                            <button wire:click="selectAllThreads" class="text-xs font-medium text-blue-600 hover:text-blue-700">All</button>
                            <button wire:click="confirmDelete" @disabled(empty($selectedThreads))
                                class="text-xs font-medium text-rose-600 hover:text-rose-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                Delete
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mt-3 relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="contactSearch" type="text" placeholder="Search people"
                            class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 border border-transparent rounded-xl placeholder:text-gray-400 focus:bg-white focus:border-gray-200 focus:ring-0">
                    </div>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto px-2 pb-3">
                @forelse ($contacts as $c)
                    @php
                        $u        = $c['user'];
                        $cid      = $c['conversation_id'];
                        $selected = $cid && in_array($cid, $selectedThreads);
                        $last     = $c['last'];
                        $mineLast = $last && $last->sender_id === $myId;
                    @endphp
                    <div wire:key="contact-{{ $u->id }}" class="group relative">
                        <button
                            @if ($selectMode)
                                @if ($cid) wire:click="toggleThread({{ $cid }})" @else disabled @endif
                            @else
                                wire:click="openChat({{ $u->id }})"
                            @endif
                            class="w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-left transition-colors
                                   {{ !$selectMode && $selectedUserId === $u->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}
                                   {{ $selected ? 'bg-blue-50' : '' }}
                                   {{ $selectMode && !$cid ? 'opacity-40 cursor-not-allowed' : '' }}">

                            @if ($selectMode)
                                <span class="flex-shrink-0 w-5 h-5 rounded-full border flex items-center justify-center transition-colors
                                             {{ $selected ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300' }}">
                                    @if ($selected)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    @endif
                                </span>
                            @endif

                            <div class="relative flex-shrink-0">
                                @if ($u->image)
                                    <img src="{{ $u->image }}" class="w-11 h-11 rounded-full object-cover" alt="">
                                @else
                                    <span class="w-11 h-11 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-semibold">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                {{-- Line 1: name + time. The time lives here alone, so the
                                     hover actions on line 2 can never sit on top of it. --}}
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $u->name }}</p>
                                    @if ($last)
                                        <span class="text-[11px] text-gray-400 flex-shrink-0 tabular-nums">{{ $last->created_at->format('h:i A') }}</span>
                                    @endif
                                </div>

                                {{-- Line 2: preview on the left, hover actions / unread on the right. --}}
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="flex-1 min-w-0 text-xs text-gray-500 truncate flex items-center gap-1">
                                        @if ($last)
                                            @if ($mineLast)
                                                <x-chat-ticks :state="$last->deliveryState()" tone="dark" />
                                            @endif
                                            <span class="truncate">
                                                @if ($last->body)
                                                    {{ $last->body }}
                                                @elseif ($last->attachment_type === 'image')
                                                    Photo
                                                @elseif ($last->attachment_type)
                                                    {{ $last->attachment_name }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="truncate text-gray-400">{{ $c['role_label'] }} · Tap to start</span>
                                        @endif
                                    </p>

                                    <span class="flex items-center gap-1.5 flex-shrink-0">
                                        @if ($c['pinned'])
                                            <svg class="w-3.5 h-3.5 text-gray-400 {{ $selectMode ? '' : 'group-hover:hidden' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M16 3v2l1 1v4l2 2v2h-6v6l-1 1-1-1v-6H5v-2l2-2V6l1-1V3h8z"/></svg>
                                        @endif
                                        @if (!$selectMode && $c['unread'] > 0)
                                            <span class="min-w-[18px] h-[18px] px-1.5 rounded-full bg-blue-600 text-white text-[10px] font-semibold flex items-center justify-center group-hover:hidden">{{ $c['unread'] > 99 ? '99+' : $c['unread'] }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </button>

                        {{-- Hover actions, anchored to line 2 — never overlapping the timestamp. --}}
                        @if (!$selectMode && $cid)
                            <div class="absolute right-3 bottom-2.5 hidden group-hover:flex items-center gap-0.5">
                                <button wire:click="togglePinThread({{ $cid }})" title="{{ $c['pinned'] ? 'Unpin chat' : 'Pin chat' }}"
                                    class="p-1 rounded-md text-gray-400 hover:text-blue-600 hover:bg-white">
                                    <svg class="w-4 h-4" fill="{{ $c['pinned'] ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 3v2l1 1v4l2 2v2h-6v6l-1 1-1-1v-6H5v-2l2-2V6l1-1V3h8z"/></svg>
                                </button>
                                <button wire:click="confirmDeleteThread({{ $cid }})" title="Delete chat"
                                    class="p-1 rounded-md text-gray-400 hover:text-rose-600 hover:bg-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
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
        <div class="relative flex-1 flex flex-col min-w-0 bg-gray-50/60 {{ $selectedUserId ? 'flex' : 'hidden sm:flex' }}">
            @if ($otherUser)
                {{-- Header — swaps to a selection bar while messages are selected --}}
                @if ($msgSelectMode)
                    <div class="flex items-center gap-1 px-3 py-2.5 bg-white border-b border-gray-100 flex-shrink-0">
                        <button wire:click="exitMessageSelect" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100" title="Cancel">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        <span class="text-sm font-medium text-gray-800 ml-1">{{ count($selectedMessages) }} selected</span>

                        <div class="ml-auto flex items-center gap-0.5">
                            <button wire:click="selectAllMessages" title="Select all"
                                class="hidden sm:inline-flex text-xs font-medium text-blue-600 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg">All</button>
                            <button wire:click="pinSelected" title="Pin"
                                class="p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 3v2l1 1v4l2 2v2h-6v6l-1 1-1-1v-6H5v-2l2-2V6l1-1V3h8z"/></svg>
                            </button>
                            <button wire:click="copySelected" title="Copy"
                                class="p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </button>
                            <button wire:click="openForward" title="Forward"
                                class="p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h13m0 0l-4-4m4 4l-4 4M17 6l4 6-4 6" /></svg>
                            </button>
                            <button wire:click="confirmDeleteMessages" title="Delete for me"
                                class="p-2 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 px-3 sm:px-4 py-2.5 bg-white border-b border-gray-100 flex-shrink-0">
                        <button wire:click="closeChat" class="sm:hidden p-1.5 -ml-1 rounded-lg text-gray-500 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        @if ($otherUser->image)
                            <img src="{{ $otherUser->image }}" class="w-9 h-9 rounded-full object-cover" alt="">
                        @else
                            <span class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-semibold">{{ strtoupper(substr($otherUser->name, 0, 1)) }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate leading-tight">{{ $otherUser->name }}</p>
                            <p class="text-[11px] text-gray-400">{{ $otherRoleLabel }}</p>
                        </div>

                        @if ($conversationId)
                            <div class="ml-auto relative" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100" title="Chat options">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                </button>
                                <div x-show="open" x-cloak style="display:none" x-transition.opacity.duration.120ms
                                     class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-lg ring-1 ring-gray-900/5 py-1 z-30">
                                    <button wire:click="selectAllMessages" @click="open = false"
                                        class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Select messages</button>
                                    <button wire:click="togglePinThread({{ $conversationId }})" @click="open = false"
                                        class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Pin / unpin chat</button>
                                    <button wire:click="confirmDeleteThread({{ $conversationId }})" @click="open = false"
                                        class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Delete chat for me</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Pinned messages bar --}}
                @if ($pinnedMessages->isNotEmpty())
                    @php $topPin = $pinnedMessages->last(); @endphp
                    <div class="flex items-center gap-2 px-4 py-2 bg-amber-50/70 border-b border-amber-100 flex-shrink-0">
                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M16 3v2l1 1v4l2 2v2h-6v6l-1 1-1-1v-6H5v-2l2-2V6l1-1V3h8z"/></svg>
                        <button type="button" @click="window.lmsChatScrollTo({{ $topPin->id }})"
                            class="flex-1 min-w-0 text-left text-xs text-gray-600 truncate hover:text-gray-900">
                            {{ $topPin->body ?: ($topPin->attachment_name ?: 'Attachment') }}
                        </button>
                        @if ($pinnedMessages->count() > 1)
                            <span class="text-[11px] text-amber-600 flex-shrink-0">{{ $pinnedMessages->count() }} pinned</span>
                        @endif
                        <button wire:click="togglePinMessage({{ $topPin->id }})" title="Unpin"
                            class="flex-shrink-0 p-1 rounded-md text-amber-500 hover:text-amber-700 hover:bg-amber-100">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                @endif

                {{-- Messages --}}
                <div id="chatScroll" data-cid="{{ $conversationId }}" data-preserve-scroll
                     class="flex-1 overflow-y-auto px-3 sm:px-6 py-4 space-y-1">
                    @php $lastDay = null; @endphp
                    @forelse ($messages as $m)
                        @php
                            $mine     = $m->sender_id === $myId;
                            $picked   = in_array($m->id, $selectedMessages);
                            $day      = $m->created_at->toDateString();
                            $newDay   = $day !== $lastDay;
                            $lastDay  = $day;
                        @endphp

                        @if ($newDay)
                            <div class="flex justify-center py-3" wire:key="day-{{ $day }}">
                                <span class="text-[11px] text-gray-400 bg-white px-3 py-1 rounded-full ring-1 ring-gray-100">
                                    {{ $m->created_at->isToday() ? 'Today' : ($m->created_at->isYesterday() ? 'Yesterday' : $m->created_at->format('d M Y')) }}
                                </span>
                            </div>
                        @endif

                        <div id="chat-msg-{{ $m->id }}" wire:key="msg-{{ $m->id }}" data-mine="{{ $mine ? 1 : 0 }}"
                             class="group flex items-center gap-2 -mx-2 px-2 py-0.5 rounded-lg transition-colors
                                    {{ $picked ? 'bg-blue-50' : '' }}
                                    {{ $msgSelectMode ? 'cursor-pointer' : '' }}"
                             @if ($msgSelectMode) wire:click="toggleMessage({{ $m->id }})" @endif>

                            @if ($msgSelectMode)
                                <span class="flex-shrink-0 w-5 h-5 rounded-full border flex items-center justify-center
                                             {{ $picked ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 bg-white' }}">
                                    @if ($picked)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    @endif
                                </span>
                            @endif

                            <div class="flex-1 min-w-0 flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div class="flex items-center gap-1 max-w-[85%] sm:max-w-[68%] {{ $mine ? 'flex-row' : 'flex-row-reverse' }}">

                                    {{-- Opens the shared menu below; the panel itself is
                                         teleported so the thread can't clip it. --}}
                                    @if (!$msgSelectMode)
                                        <button type="button" title="Message options"
                                            @click.stop="openMenu($el, { id: {{ $m->id }}, pinned: {{ $m->pinned_at ? 'true' : 'false' }}, body: @js($m->body) })"
                                            class="flex-shrink-0 p-1 rounded-md text-gray-300 hover:text-gray-600 hover:bg-white transition-opacity
                                                   opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                                            :class="menu.open && menu.id === {{ $m->id }} ? 'opacity-100 sm:opacity-100 text-gray-600' : ''">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                        </button>
                                    @endif

                                    <div class="min-w-0 rounded-2xl px-3 py-2
                                                {{ $mine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-white text-gray-800 ring-1 ring-gray-100 rounded-bl-sm' }}">

                                        @if ($m->forwarded_from_id)
                                            <p class="flex items-center gap-1 text-[10px] italic mb-0.5 {{ $mine ? 'text-blue-100' : 'text-gray-400' }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h13m0 0l-4-4m4 4l-4 4M17 6l4 6-4 6" /></svg>
                                                Forwarded
                                            </p>
                                        @endif

                                        @if ($m->attachment_type === 'image' && $m->attachment_url)
                                            <a href="{{ route('chat.attachment', $m->id) }}" target="_blank" rel="noopener">
                                                <img src="{{ route('chat.attachment', $m->id) }}" loading="lazy"
                                                     class="rounded-xl max-h-64 w-auto mb-1" alt="{{ $m->attachment_name ?: 'attachment' }}">
                                            </a>
                                        @elseif ($m->attachment_type && $m->attachment_url)
                                            <a href="{{ route('chat.attachment', $m->id) }}" target="_blank" rel="noopener"
                                               class="flex items-center gap-2 mb-1 px-2 py-1.5 rounded-xl {{ $mine ? 'bg-blue-500/40' : 'bg-gray-50' }}">
                                                <svg class="w-5 h-5 flex-shrink-0 {{ $mine ? 'text-white' : 'text-blue-600' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                                <span class="text-xs truncate max-w-[180px] {{ $mine ? 'text-white' : 'text-gray-700' }}">{{ $m->attachment_name ?: 'Document' }}</span>
                                            </a>
                                        @endif

                                        @if ($m->body)
                                            <p class="text-sm whitespace-pre-line break-words leading-relaxed">{{ $m->body }}</p>
                                        @endif

                                        <div class="flex items-center justify-end gap-1 mt-0.5 -mb-0.5">
                                            @if ($m->pinned_at)
                                                <svg class="w-3 h-3 {{ $mine ? 'text-blue-200' : 'text-amber-500' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M16 3v2l1 1v4l2 2v2h-6v6l-1 1-1-1v-6H5v-2l2-2V6l1-1V3h8z"/></svg>
                                            @endif
                                            <span class="text-[10px] tabular-nums {{ $mine ? 'text-blue-100' : 'text-gray-400' }}">{{ $m->created_at->format('h:i A') }}</span>
                                            @if ($mine)
                                                <x-chat-ticks :state="$m->deliveryState()" tone="light" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex items-center justify-center text-sm text-gray-400">No messages yet. Say hello 👋</div>
                    @endforelse
                </div>

                {{-- Messages that land while you are reading older ones announce
                     themselves here instead of dragging the thread to the bottom. --}}
                <button type="button" id="chatNewPill" style="display:none"
                        onclick="window.lmsChatToBottom && window.lmsChatToBottom()"
                        class="absolute left-1/2 -translate-x-1/2 bottom-24 z-20 items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-600 text-white text-xs font-semibold shadow-lg hover:bg-blue-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                    New messages
                </button>

                {{-- Composer --}}
                <div class="bg-white border-t border-gray-100 px-3 py-3 flex-shrink-0">
                    @error('body') <p class="text-xs text-rose-500 mb-1.5 px-1">{{ $message }}</p> @enderror
                    @error('attachment') <p class="text-xs text-rose-500 mb-1.5 px-1">{{ $message }}</p> @enderror

                    @if ($attachment)
                        <div class="flex items-center gap-2 mb-2 px-2.5 py-1.5 bg-gray-50 rounded-xl text-xs text-gray-600">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            <span class="truncate flex-1">{{ $attachment->getClientOriginalName() }}</span>
                            <button type="button" wire:click="$set('attachment', null)" class="text-rose-500 hover:text-rose-700">Remove</button>
                        </div>
                    @endif
                    <div wire:loading wire:target="attachment" class="text-xs text-blue-600 mb-1.5 px-1">Uploading attachment…</div>

                    <form x-data="{
                              draft: '',
                              send() {
                                  if (this.draft.trim() === '' && !$wire.attachment) return;
                                  const text = this.draft;
                                  this.draft = '';
                                  $refs.input.style.height = 'auto';
                                  $wire.sendMessage(text);
                              },
                              grow() {
                                  const el = $refs.input;
                                  el.style.height = 'auto';
                                  el.style.height = Math.min(el.scrollHeight, 128) + 'px';
                              },
                          }"
                          @submit.prevent="send()"
                          class="flex items-end gap-2">
                        <label class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer" title="Attach image or document">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            <input type="file" wire:model="attachment" class="hidden" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                        </label>

                        {{-- Enter sends, Shift+Enter starts a new line. `isComposing`
                             keeps IME (and Android keyboard) candidates from sending. --}}
                        <textarea x-ref="input" x-model="draft" rows="1" placeholder="Type a message"
                            @input="grow()"
                            @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); send(); }"
                            class="flex-1 resize-none bg-gray-50 border border-transparent rounded-2xl px-4 py-2.5 text-sm leading-relaxed placeholder:text-gray-400 focus:bg-white focus:border-gray-200 focus:ring-0 max-h-32"></textarea>

                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                            class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white flex items-center justify-center transition-colors">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </button>
                    </form>
                </div>
            @else
                {{-- Empty state --}}
                <div class="flex-1 flex flex-col items-center justify-center text-center px-6">
                    <div class="w-14 h-14 rounded-2xl bg-white ring-1 ring-gray-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.83L3 20l1.4-3.5A7.9 7.9 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800">Your messages</h3>
                    <p class="text-xs text-gray-400 mt-1 max-w-xs">Pick someone on the left to start a conversation.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         Message actions — one panel for every bubble, teleported out of the
         scrolling thread so it can never be clipped, and flipped above the
         button when it would run off the bottom of the window.
    ══════════════════════════════════════════════════ --}}
    @teleport('body')
    <div x-show="menu.open" x-cloak style="display:none"
         x-transition.opacity.duration.100ms
         @click.outside="menu.open = false"
         :style="'top:' + menu.top + 'px; left:' + menu.left + 'px'"
         class="fixed z-[80] w-44 bg-white rounded-xl shadow-xl ring-1 ring-gray-900/5 py-1">
        <button type="button" @click="run(() => $wire.startMessageSelect(menu.id))"
            class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Select</button>
        <button type="button" @click="run(() => $wire.togglePinMessage(menu.id))"
            class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
            x-text="menu.pinned ? 'Unpin' : 'Pin'">Pin</button>
        <button type="button" x-show="menu.body" @click="run(() => copy(menu.body))"
            class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Copy</button>
        <button type="button" @click="run(() => $wire.forwardMessage(menu.id))"
            class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Forward</button>
        <button type="button" @click="run(() => $wire.confirmDeleteMessage(menu.id))"
            class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Delete for me</button>
    </div>
    @endteleport

    {{-- ══════════════════════════════════════════════════
         Delete chat (one-sided)
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        @php $count = count($selectedThreads); @endphp
        @teleport('body')
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center bg-gray-900/30 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-sm font-semibold text-gray-900">Delete {{ $count > 1 ? $count . ' chats' : 'this chat' }}?</h3>
                <p class="text-sm text-gray-500 mt-2 mb-5">
                    {{ $count > 1 ? 'They' : 'It' }} will be removed from <strong class="text-gray-700">your</strong> list only.
                    The other person keeps their copy until they delete it themselves.
                </p>
                <div class="flex items-center gap-2">
                    <button wire:click="deleteSelected"
                        class="flex-1 py-2 bg-rose-500 hover:bg-rose-600 text-white text-sm font-medium rounded-xl transition-colors">Delete for me</button>
                    <button wire:click="$set('showDeleteConfirm', false)"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-xl transition-colors">Cancel</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         Delete messages (one-sided)
    ══════════════════════════════════════════════════ --}}
    @if ($showMsgDeleteConfirm)
        @php $count = count($selectedMessages); @endphp
        @teleport('body')
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center bg-gray-900/30 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-sm font-semibold text-gray-900">Delete {{ $count > 1 ? $count . ' messages' : 'this message' }}?</h3>
                <p class="text-sm text-gray-500 mt-2 mb-5">
                    {{ $count > 1 ? 'They disappear' : 'It disappears' }} from <strong class="text-gray-700">your</strong> chat only —
                    the other person still sees {{ $count > 1 ? 'them' : 'it' }}.
                </p>
                <div class="flex items-center gap-2">
                    <button wire:click="deleteSelectedMessages"
                        class="flex-1 py-2 bg-rose-500 hover:bg-rose-600 text-white text-sm font-medium rounded-xl transition-colors">Delete for me</button>
                    <button wire:click="$set('showMsgDeleteConfirm', false)"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-xl transition-colors">Cancel</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         Forward picker
    ══════════════════════════════════════════════════ --}}
    @if ($showForward)
        @teleport('body')
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center bg-gray-900/30 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm flex flex-col max-h-[80vh]">
                <div class="px-5 pt-5 pb-3">
                    <h3 class="text-sm font-semibold text-gray-900">
                        Forward {{ count($selectedMessages) > 1 ? count($selectedMessages) . ' messages' : 'message' }}
                    </h3>
                    <input wire:model.live.debounce.300ms="forwardSearch" type="text" placeholder="Search people"
                        class="mt-3 w-full px-3 py-2 text-sm bg-gray-50 border border-transparent rounded-xl placeholder:text-gray-400 focus:bg-white focus:border-gray-200 focus:ring-0">
                </div>

                <div class="flex-1 overflow-y-auto px-2">
                    @forelse ($forwardContacts as $u)
                        @php $on = in_array($u->id, $forwardTo); @endphp
                        <button wire:key="fwd-{{ $u->id }}" wire:click="toggleForwardTarget({{ $u->id }})"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left hover:bg-gray-50 {{ $on ? 'bg-blue-50' : '' }}">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full border flex items-center justify-center
                                         {{ $on ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300' }}">
                                @if ($on)
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                @endif
                            </span>
                            @if ($u->image)
                                <img src="{{ $u->image }}" class="w-9 h-9 rounded-full object-cover" alt="">
                            @else
                                <span class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-semibold">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                            @endif
                            <span class="min-w-0">
                                <span class="block text-sm text-gray-800 truncate">{{ $u->name }}</span>
                                <span class="block text-[11px] text-gray-400">{{ \App\Livewire\Chat\Messenger::roleLabel($u->role) }}</span>
                            </span>
                        </button>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-gray-400">No one found.</p>
                    @endforelse
                </div>

                <div class="flex items-center gap-2 px-5 py-4 border-t border-gray-100">
                    <button wire:click="forwardSelected" @disabled(empty($forwardTo))
                        class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                        Send{{ count($forwardTo) ? ' to ' . count($forwardTo) : '' }}
                    </button>
                    <button wire:click="closeForward"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-xl transition-colors">Cancel</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Thread scrolling. The one rule: only the reader decides where the
         thread sits. We follow the newest message while the reader is already
         at the bottom; the moment they scroll up to read older messages, every
         poll/morph puts the view back exactly where they left it and new
         messages announce themselves with a pill instead of yanking them down. --}}
    @script
    <script>
        // One instance per tab. Re-mounting the component (wire:navigate back
        // into chat) must not leave a second copy of this state behind — two
        // copies disagreeing about `stick` is another way the thread jumps.
        if (!window.__lmsChatSync) {
        window.__lmsChatSync = 1;

        const NEAR     = 120;                     // px from the bottom that still counts as "at the bottom"
        const scroller = () => document.getElementById('chatScroll');
        const pill     = () => document.getElementById('chatNewPill');

        let stick   = true;    // follow the newest message?
        let pending = false;   // unseen messages while reading older ones
        let keepTop = 0;       // the scroll position the reader chose
        let seen    = 0;
        let cid     = null;

        const nearBottom = (el) => el.scrollHeight - el.scrollTop - el.clientHeight < NEAR;

        // The pill lives inside the component, so a morph resets its inline
        // style — repaint it from `pending` after every sync.
        const paintPill = () => {
            const el = pill();
            if (el) el.style.display = pending ? 'inline-flex' : 'none';
        };

        const toBottom = () => {
            const el = scroller();
            if (!el) return;
            el.scrollTop = el.scrollHeight;
            keepTop = el.scrollTop;
            stick   = true;
            pending = false;
            paintPill();
        };
        window.lmsChatToBottom = toBottom;        // the pill taps this

        window.lmsChatScrollTo = (id) => {
            const el = document.getElementById('chat-msg-' + id);
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('ring-2', 'ring-amber-300');
            setTimeout(() => el.classList.remove('ring-2', 'ring-amber-300'), 1600);
        };

        const newest = () => {
            const nodes = document.querySelectorAll('#chatScroll [id^="chat-msg-"]');
            const last  = nodes[nodes.length - 1];
            if (!last) return { id: 0, mine: true };
            return { id: parseInt(last.id.replace('chat-msg-', ''), 10), mine: last.dataset.mine === '1' };
        };

        // Only the reader's own scrolling moves `stick`. Measuring it after a
        // morph is what used to drag the thread back down: re-rendering the
        // thread nudges scrollTop by itself (content is clamped, nodes are
        // replaced), the browser fires a scroll event for that nudge, and a
        // nudge towards the bottom read as "they're at the bottom".
        // So scroll events fired inside a re-render window are ignored —
        // unless the reader really is working the wheel/finger right then.
        let morphUntil = 0, userAt = 0;
        const stamp = () => { userAt = Date.now(); };
        ['wheel', 'touchstart', 'touchmove', 'keydown', 'mousedown'].forEach(
            (ev) => document.addEventListener(ev, stamp, { capture: true, passive: true }));

        // Delegated in the capture phase because the container element itself
        // is re-rendered on every poll.
        document.addEventListener('scroll', (e) => {
            const el = e.target;
            if (!el || el.id !== 'chatScroll') return;
            if (Date.now() < morphUntil && Date.now() - userAt > 400) return;
            keepTop = el.scrollTop;
            stick   = nearBottom(el);
            if (stick && pending) { pending = false; paintPill(); }
        }, true);

        const sync = () => {
            const el = scroller();
            if (!el) return;
            const tip    = newest();
            const nowCid = el.dataset.cid;

            // Switching threads re-baselines, so an older chat never chimes.
            if (nowCid !== cid) {
                cid  = nowCid;
                seen = tip.id;
                pending = false;
                toBottom();
                return;
            }

            if (tip.id > seen) {
                // Chime only for messages that arrived — never for my own.
                if (seen !== 0 && !tip.mine && window.lmsPlayNotifSound) window.lmsPlayNotifSound();
                seen = tip.id;
                if (stick || tip.mine) { toBottom(); return; }   // my own send always follows
                pending = true;                                  // theirs waits behind the pill
            }

            if (stick) { toBottom(); return; }

            // Reading older messages: undo whatever the morph did to the scroll.
            if (Math.abs(el.scrollTop - keepTop) > 1) el.scrollTop = keepTop;
            paintPill();
        };

        // morph.updated fires per morphed element; one sync per commit is enough.
        let syncT;
        const scheduleSync = () => { clearTimeout(syncT); syncT = setTimeout(sync, 30); };

        // The actions panel is positioned in viewport coordinates, so anything
        // that moves the bubble under it has to dismiss it.
        const closeMenu = () => window.dispatchEvent(new CustomEvent('chat-menu-close'));
        document.addEventListener('scroll', closeMenu, true);
        window.addEventListener('resize', closeMenu);

        // Landing on the thread (first paint or a wire:navigate remount) starts
        // at the newest message.
        const baseline = () => {
            const el = scroller();
            if (!el) return;
            seen    = newest().id;
            cid     = el.dataset.cid;
            pending = false;
            toBottom();
        };
        window.lmsChatBaseline = baseline;
        document.addEventListener('livewire:navigated', baseline);
        baseline();

        Livewire.hook('morph.updated', scheduleSync);
        Livewire.hook('commit', ({ succeed }) => {
            // Read the thread's real position BEFORE the re-render lands — this,
            // not a post-morph measurement, is what "where the reader was" means.
            const el = scroller();
            if (el) { keepTop = el.scrollTop; stick = nearBottom(el); }
            morphUntil = Date.now() + 200;
            if (typeof succeed === 'function') {
                succeed(() => { morphUntil = Date.now() + 200; scheduleSync(); });
            }
        });

        } else if (window.lmsChatBaseline) {
            window.lmsChatBaseline();
        }
    </script>
    @endscript
</div>
