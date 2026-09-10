<?php

namespace App\Livewire\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

/**
 * Near-real-time messaging between everyone in a school (organization):
 * admins, sub-admins and the accounts team can all chat with each other.
 * Uses Livewire polling for live updates (no websocket infra). One-to-one
 * conversations; the schema also supports groups.
 *
 * Deletes are one-sided: removing a message (or a whole chat) only hides it
 * for the person who did it — the other side keeps their copy until they
 * delete it themselves.
 */
class Messenger extends Component
{
    use WithFileUploads, WireUiActions;

    public ?int   $conversationId = null;
    public ?int   $selectedUserId = null;
    public string $body           = '';
    public        $attachment     = null;
    public string $contactSearch  = '';

    /** Thread (left list) multi-select state. */
    public bool  $selectMode        = false;
    public array $selectedThreads   = []; // conversation ids
    public bool  $showDeleteConfirm = false;

    /** Message (right pane) multi-select state. */
    public bool  $msgSelectMode        = false;
    public array $selectedMessages     = []; // message ids
    public bool  $showMsgDeleteConfirm = false;

    /** Forwarding. */
    public bool   $showForward   = false;
    public array  $forwardTo     = []; // user ids
    public string $forwardSearch = '';

    /** Roles that share this organization-wide chat. */
    protected const CHAT_ROLES = ['admin', 'sub-admin', 'accounts'];

    public function mount(): void
    {
        if (!in_array(Auth::user()->role, self::CHAT_ROLES, true)) {
            abort(403);
        }
    }

    protected function meId(): int
    {
        return (int) Auth::id();
    }

    protected function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    public static function roleLabel(?string $role): string
    {
        return match ($role) {
            'admin'     => 'Admin',
            'sub-admin' => 'Sub-admin',
            'accounts'  => 'Accounts',
            default     => ucfirst((string) $role),
        };
    }

    /**
     * The people the current user is allowed to chat with: everyone else in
     * the same organization whose role is admin / sub-admin / accounts but is
     * a different role than the current user (cross-role chat).
     */
    protected function candidateQuery()
    {
        return User::where('organization_id', $this->orgId())
            ->where('is_active', 1)
            ->whereIn('role', self::CHAT_ROLES)
            ->where('role', '!=', Auth::user()->role)
            ->where('id', '!=', $this->meId());
    }

    public function openChat(int $userId): void
    {
        // In select mode a row tap toggles selection, never opens the thread.
        if ($this->selectMode) {
            return;
        }

        $other = $this->candidateQuery()->where('id', $userId)->first();
        if (!$other) {
            return; // not a valid counterpart — ignore
        }

        $conversation = $this->findOrCreateConversation($other->id);

        $this->conversationId = $conversation->id;
        $this->selectedUserId = $other->id;
        $this->reset(['body']);
        $this->attachment = null;
        $this->exitMessageSelect();
        $this->resetValidation();
        $this->markRead();
    }

    public function closeChat(): void
    {
        $this->selectedUserId = null;
        $this->conversationId = null;
        $this->exitMessageSelect();
    }

    protected function findOrCreateConversation(int $otherId): Conversation
    {
        $meId  = $this->meId();
        $orgId = $this->orgId();

        $existing = Conversation::where('organization_id', $orgId)
            ->whereHas('participants', fn($q) => $q->where('user_id', $meId))
            ->whereHas('participants', fn($q) => $q->where('user_id', $otherId))
            ->withCount('participants')
            ->get()
            ->firstWhere('participants_count', 2);

        if ($existing) {
            return $existing;
        }

        $conversation = Conversation::create([
            'organization_id' => $orgId,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$meId, $otherId]);

        return $conversation;
    }

    /** Only conversations the current user actually belongs to. */
    protected function currentConversation(): ?Conversation
    {
        if ($this->conversationId === null) {
            return null;
        }

        return Conversation::where('organization_id', $this->orgId())
            ->whereHas('participants', fn($q) => $q->where('user_id', $this->meId()))
            ->find($this->conversationId);
    }

    public function sendMessage(?string $body = null): void
    {
        // The composer keeps its draft in Alpine (poll-safe) and passes it in.
        if ($body !== null) {
            $this->body = $body;
        }

        $conversation = $this->currentConversation();
        if (!$conversation) {
            return;
        }

        $this->validate([
            'body'       => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
        ], [
            'attachment.max'   => 'Attachment must not exceed 10 MB.',
            'attachment.mimes' => 'Only images and common documents are allowed.',
        ]);

        if (trim($this->body) === '' && !$this->attachment) {
            $this->addError('body', 'Type a message or attach a file.');
            return;
        }

        $data = [
            'conversation_id' => $conversation->id,
            'sender_id'       => $this->meId(),
            'body'            => trim($this->body) ?: null,
        ];

        if ($this->attachment) {
            $path = $this->attachment->store('chat/attachments', 's3');

            $mime = (string) $this->attachment->getMimeType();
            // The object stays private on the bucket; it is read back through
            // route('chat.attachment'), which checks the viewer is in the chat.
            $data['attachment_url']  = Storage::disk('s3')->url($path);
            $data['attachment_path'] = $path;
            $data['attachment_name'] = $this->attachment->getClientOriginalName();
            $data['attachment_type'] = str_starts_with($mime, 'image/') ? 'image' : 'file';
            $data['attachment_size'] = (int) $this->attachment->getSize();
        }

        Message::create($data);
        $conversation->update(['last_message_at' => now()]);

        // Sending re-opens a chat the sender had cleared for themselves.
        $conversation->participants()->updateExistingPivot($this->meId(), ['cleared_at' => null]);

        $this->reset(['body']);
        $this->attachment = null;
        $this->resetValidation();
    }

    /** Mark all messages from the other party in the open conversation as read. */
    public function markRead(): void
    {
        if ($this->conversationId === null) {
            return;
        }

        // Reading also implies delivery, for anything the poller hasn't caught yet.
        Message::where('conversation_id', $this->conversationId)
            ->where('sender_id', '!=', $this->meId())
            ->whereNull('read_at')
            ->update([
                'read_at'      => now(),
                'delivered_at' => DB::raw('COALESCE(delivered_at, NOW())'),
            ]);

        $conversation = $this->currentConversation();
        $conversation?->participants()->updateExistingPivot($this->meId(), ['last_read_at' => now()]);
    }

    // ─── Threads: select, pin, delete-for-me ────────────────────────────────

    /** Ids of every conversation the current user belongs to. */
    protected function myConversationIds(): array
    {
        return Conversation::where('organization_id', $this->orgId())
            ->whereHas('participants', fn($q) => $q->where('user_id', $this->meId()))
            ->pluck('id')
            ->all();
    }

    public function toggleSelectMode(): void
    {
        $this->selectMode      = !$this->selectMode;
        $this->selectedThreads = [];
    }

    public function toggleThread(int $conversationId): void
    {
        if (in_array($conversationId, $this->selectedThreads, true)) {
            $this->selectedThreads = array_values(array_diff($this->selectedThreads, [$conversationId]));
        } else {
            $this->selectedThreads[] = $conversationId;
        }
    }

    public function selectAllThreads(): void
    {
        $all = $this->myConversationIds();
        // Toggle: if everything is already selected, clear; otherwise select all.
        $this->selectedThreads = count($this->selectedThreads) === count($all) ? [] : $all;
    }

    /** Pin / unpin a chat for me only — it sorts to the top of my list. */
    public function togglePinThread(int $conversationId): void
    {
        $conversation = Conversation::where('organization_id', $this->orgId())
            ->whereHas('participants', fn($q) => $q->where('user_id', $this->meId()))
            ->find($conversationId);

        if (!$conversation) {
            return;
        }

        $pinned = DB::table('chat_conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $this->meId())
            ->value('pinned_at');

        $conversation->participants()->updateExistingPivot($this->meId(), [
            'pinned_at' => $pinned ? null : now(),
        ]);
    }

    /** Single-thread delete (trash icon) — routes through the same confirm modal. */
    public function confirmDeleteThread(int $conversationId): void
    {
        $this->selectedThreads   = [$conversationId];
        $this->showDeleteConfirm = true;
    }

    public function confirmDelete(): void
    {
        if (empty($this->selectedThreads)) {
            return;
        }
        $this->showDeleteConfirm = true;
    }

    /**
     * Clear the selected chats for me only. The conversation, its messages and
     * the other person's copy all stay exactly where they were.
     */
    public function deleteSelected(): void
    {
        $ids  = array_map('intval', $this->selectedThreads);
        $meId = $this->meId();

        // Restrict to conversations the current user is actually part of.
        $owned = Conversation::where('organization_id', $this->orgId())
            ->whereIn('id', $ids)
            ->whereHas('participants', fn($q) => $q->where('user_id', $meId))
            ->pluck('id')
            ->all();

        if (!empty($owned)) {
            $messageIds = Message::whereIn('conversation_id', $owned)->pluck('id')->all();

            DB::transaction(function () use ($owned, $messageIds, $meId) {
                $this->hideMessagesForMe($messageIds);
                DB::table('chat_conversation_user')
                    ->whereIn('conversation_id', $owned)
                    ->where('user_id', $meId)
                    ->update(['cleared_at' => now()]);
            });

            if (in_array((int) $this->conversationId, $owned, true)) {
                $this->closeChat();
            }
        }

        $this->selectedThreads   = [];
        $this->selectMode        = false;
        $this->showDeleteConfirm = false;

        if (!empty($owned)) {
            $this->dispatch('notify', type: 'success', message: count($owned) > 1 ? 'Chats deleted' : 'Chat deleted');
        }
    }

    // ─── Messages: select, pin, copy, forward, delete ───────────────────────

    /** Message ids in the open conversation that I can still see. */
    protected function visibleMessageIds(): array
    {
        if ($this->conversationId === null) {
            return [];
        }

        return Message::where('conversation_id', $this->conversationId)
            ->visibleTo($this->meId())
            ->pluck('id')
            ->all();
    }

    public function exitMessageSelect(): void
    {
        $this->msgSelectMode        = false;
        $this->selectedMessages     = [];
        $this->showMsgDeleteConfirm = false;
        $this->showForward          = false;
        $this->forwardTo            = [];
        $this->forwardSearch        = '';
    }

    public function startMessageSelect(int $messageId): void
    {
        $this->msgSelectMode    = true;
        $this->selectedMessages = [$messageId];
    }

    public function toggleMessage(int $messageId): void
    {
        if (in_array($messageId, $this->selectedMessages, true)) {
            $this->selectedMessages = array_values(array_diff($this->selectedMessages, [$messageId]));
            if (empty($this->selectedMessages)) {
                $this->msgSelectMode = false;
            }
        } else {
            $this->msgSelectMode      = true;
            $this->selectedMessages[] = $messageId;
        }
    }

    public function selectAllMessages(): void
    {
        $all = $this->visibleMessageIds();
        $this->selectedMessages = count($this->selectedMessages) === count($all) ? [] : $all;
        $this->msgSelectMode    = !empty($this->selectedMessages);
    }

    /** Only messages from the open conversation that I'm allowed to touch. */
    protected function ownedMessages(array $ids)
    {
        if ($this->conversationId === null || empty($ids)) {
            return collect();
        }

        return Message::where('conversation_id', $this->conversationId)
            ->whereIn('id', array_map('intval', $ids))
            ->visibleTo($this->meId())
            ->orderBy('id')
            ->get();
    }

    /** Pin / unpin a message — pinned messages surface in the thread's pin bar. */
    public function togglePinMessage(int $messageId): void
    {
        $message = $this->ownedMessages([$messageId])->first();
        if (!$message) {
            return;
        }

        $wasPinned = $message->pinned_at !== null;
        $message->update(['pinned_at' => $wasPinned ? null : now()]);
        $this->exitMessageSelect();
        $this->dispatch('notify', type: 'success', message: $wasPinned ? 'Message unpinned' : 'Message pinned');
    }

    public function pinSelected(): void
    {
        $messages = $this->ownedMessages($this->selectedMessages);
        if ($messages->isEmpty()) {
            return;
        }

        // If every selected message is already pinned, the button unpins instead.
        $unpin = $messages->every(fn(Message $m) => $m->pinned_at !== null);
        Message::whereIn('id', $messages->pluck('id'))->update(['pinned_at' => $unpin ? null : now()]);

        $this->exitMessageSelect();
        $this->dispatch('notify', type: 'success', message: $unpin ? 'Unpinned' : 'Pinned');
    }

    /** Hand the selected text to the browser, which owns the clipboard. */
    public function copySelected(): void
    {
        $messages = $this->ownedMessages($this->selectedMessages);
        if ($messages->isEmpty()) {
            return;
        }

        $text = $messages
            ->map(fn(Message $m) => $m->body ?: ($m->attachment_name ?: ''))
            ->filter()
            ->implode("\n");

        $this->exitMessageSelect();

        if ($text === '') {
            $this->dispatch('notify', type: 'error', message: 'Nothing to copy');
            return;
        }

        $this->dispatch('chat-copy', text: $text);
    }

    public function openForward(): void
    {
        if (empty($this->selectedMessages)) {
            return;
        }
        $this->forwardTo     = [];
        $this->forwardSearch = '';
        $this->showForward   = true;
    }

    /** Forward one message straight from its own menu. */
    public function forwardMessage(int $messageId): void
    {
        $this->msgSelectMode    = true;
        $this->selectedMessages = [$messageId];
        $this->forwardTo        = [];
        $this->forwardSearch    = '';
        $this->showForward      = true;
    }

    public function closeForward(): void
    {
        $this->showForward   = false;
        $this->forwardTo     = [];
        $this->forwardSearch = '';
    }

    public function toggleForwardTarget(int $userId): void
    {
        if (in_array($userId, $this->forwardTo, true)) {
            $this->forwardTo = array_values(array_diff($this->forwardTo, [$userId]));
        } else {
            $this->forwardTo[] = $userId;
        }
    }

    public function forwardSelected(): void
    {
        $messages = $this->ownedMessages($this->selectedMessages);
        if ($messages->isEmpty() || empty($this->forwardTo)) {
            return;
        }

        $targets = $this->candidateQuery()->whereIn('id', $this->forwardTo)->pluck('id');
        if ($targets->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($messages, $targets) {
            foreach ($targets as $userId) {
                $conversation = $this->findOrCreateConversation((int) $userId);

                foreach ($messages as $m) {
                    Message::create([
                        'conversation_id'   => $conversation->id,
                        'sender_id'         => $this->meId(),
                        'forwarded_from_id' => $m->id,
                        'body'              => $m->body,
                        'attachment_url'    => $m->attachment_url,
                        'attachment_path'   => $m->attachment_path,
                        'attachment_name'   => $m->attachment_name,
                        'attachment_type'   => $m->attachment_type,
                        'attachment_size'   => $m->attachment_size,
                    ]);
                }

                $conversation->update(['last_message_at' => now()]);
                $conversation->participants()->updateExistingPivot($this->meId(), ['cleared_at' => null]);
            }
        });

        $count = $targets->count();
        $this->exitMessageSelect();
        $this->dispatch('notify', type: 'success', message: "Forwarded to {$count} " . ($count > 1 ? 'chats' : 'chat'));
    }

    public function confirmDeleteMessages(): void
    {
        if (empty($this->selectedMessages)) {
            return;
        }
        $this->showMsgDeleteConfirm = true;
    }

    public function confirmDeleteMessage(int $messageId): void
    {
        $this->msgSelectMode        = true;
        $this->selectedMessages     = [$messageId];
        $this->showMsgDeleteConfirm = true;
    }

    /** One-sided delete: the rows stay, they just stop being visible to me. */
    public function deleteSelectedMessages(): void
    {
        $messages = $this->ownedMessages($this->selectedMessages);

        if ($messages->isNotEmpty()) {
            $this->hideMessagesForMe($messages->pluck('id')->all());
        }

        $count = $messages->count();
        $this->exitMessageSelect();

        if ($count > 0) {
            $this->dispatch('notify', type: 'success', message: $count > 1 ? "{$count} messages deleted" : 'Message deleted');
        }
    }

    /** Record "deleted for me" rows, skipping any that already exist. */
    protected function hideMessagesForMe(array $messageIds): void
    {
        if (empty($messageIds)) {
            return;
        }

        $meId = $this->meId();
        $now  = now();

        $already = DB::table('chat_message_deletes')
            ->where('user_id', $meId)
            ->whereIn('message_id', $messageIds)
            ->pluck('message_id')
            ->all();

        $rows = collect($messageIds)
            ->diff($already)
            ->map(fn($id) => [
                'message_id' => (int) $id,
                'user_id'    => $meId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('chat_message_deletes')->insert($chunk);
        }
    }

    public function render()
    {
        $meId = $this->meId();

        // Every conversation I'm in, with my pivot flags, in one query.
        $myPivots = DB::table('chat_conversation_user as cu')
            ->join('chat_conversations as c', 'c.id', '=', 'cu.conversation_id')
            ->where('cu.user_id', $meId)
            ->where('c.organization_id', $this->orgId())
            ->select('cu.conversation_id', 'cu.pinned_at', 'cu.cleared_at')
            ->get()
            ->keyBy('conversation_id');

        // Map counterpart user id → conversation id (two-participant threads).
        $conversationByUser = [];
        if ($myPivots->isNotEmpty()) {
            $rows = DB::table('chat_conversation_user')
                ->whereIn('conversation_id', $myPivots->keys()->all())
                ->where('user_id', '!=', $meId)
                ->select('conversation_id', 'user_id')
                ->get();
            foreach ($rows as $row) {
                $conversationByUser[(int) $row->user_id] = (int) $row->conversation_id;
            }
        }

        // Contact list (other-panel users) with their conversation, last message and unread count.
        $contacts = $this->candidateQuery()
            ->when($this->contactSearch, fn($q) => $q->where(fn($q) => $q
                ->where('name', 'like', "%{$this->contactSearch}%")
                ->orWhere('email', 'like', "%{$this->contactSearch}%")))
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($meId, $conversationByUser, $myPivots) {
                $cid   = $conversationByUser[$u->id] ?? null;
                $pivot = $cid ? $myPivots->get($cid) : null;

                $unread = 0;
                $last   = null;
                if ($cid) {
                    $unread = Message::where('conversation_id', $cid)
                        ->visibleTo($meId)
                        ->where('sender_id', '!=', $meId)
                        ->whereNull('read_at')
                        ->count();
                    $last = Message::where('conversation_id', $cid)
                        ->visibleTo($meId)
                        ->latest('id')
                        ->first();
                }

                return [
                    'user'            => $u,
                    'role_label'      => self::roleLabel($u->role),
                    'conversation_id' => $cid,
                    'unread'          => $unread,
                    'last'            => $last,
                    'pinned'          => (bool) ($pivot->pinned_at ?? false),
                    'last_sort'       => $last?->id ?? 0,
                ];
            })
            ->sortByDesc(fn($c) => [$c['pinned'] ? 1 : 0, $c['last_sort']])
            ->values();

        // Active conversation messages (and keep them marked read while open).
        $messages = collect();
        $pinned   = collect();
        if ($this->conversationId) {
            $this->markRead();
            $messages = Message::where('conversation_id', $this->conversationId)
                ->visibleTo($meId)
                ->orderBy('id')
                ->get();
            $pinned = $messages->filter(fn(Message $m) => $m->pinned_at !== null)->values();
        }

        // Keep the selection honest if a message disappeared under us.
        if ($this->selectedMessages) {
            $this->selectedMessages = array_values(array_intersect($this->selectedMessages, $messages->pluck('id')->all()));
            if (empty($this->selectedMessages)) {
                $this->msgSelectMode = false;
            }
        }

        $other = $this->selectedUserId ? User::find($this->selectedUserId) : null;

        $forwardContacts = $this->showForward
            ? $this->candidateQuery()
                ->when($this->forwardSearch, fn($q) => $q->where('name', 'like', "%{$this->forwardSearch}%"))
                ->orderBy('name')
                ->get()
            : collect();

        return view('livewire.chat.messenger', [
            'contacts'        => $contacts,
            'messages'        => $messages,
            'pinnedMessages'  => $pinned,
            'otherUser'       => $other,
            'otherRoleLabel'  => $other ? self::roleLabel($other->role) : '',
            'myId'            => $meId,
            'forwardContacts' => $forwardContacts,
            'panelLabel'      => 'Admins · Sub-admins · Accounts',
        ]);
    }
}
