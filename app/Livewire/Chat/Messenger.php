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
 */
class Messenger extends Component
{
    use WithFileUploads, WireUiActions;

    public ?int   $conversationId = null;
    public ?int   $selectedUserId = null;
    public string $body           = '';
    public        $attachment     = null;
    public string $contactSearch  = '';

    /** Multi-select / delete state. */
    public bool  $selectMode        = false;
    public array $selectedThreads   = []; // conversation ids
    public bool  $showDeleteConfirm = false;

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
        $this->resetValidation();
        $this->markRead();
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
            Storage::disk('s3')->setVisibility($path, 'public');

            $mime = (string) $this->attachment->getMimeType();
            $data['attachment_url']  = Storage::disk('s3')->url($path);
            $data['attachment_name'] = $this->attachment->getClientOriginalName();
            $data['attachment_type'] = str_starts_with($mime, 'image/') ? 'image' : 'file';
        }

        Message::create($data);
        $conversation->update(['last_message_at' => now()]);

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

        Message::where('conversation_id', $this->conversationId)
            ->where('sender_id', '!=', $this->meId())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation = $this->currentConversation();
        $conversation?->participants()->updateExistingPivot($this->meId(), ['last_read_at' => now()]);
    }

    // ─── Select / delete ────────────────────────────────────────────────────

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

    /** Permanently remove the selected conversations, their messages and pivots. */
    public function deleteSelected(): void
    {
        $ids = array_map('intval', $this->selectedThreads);

        // Restrict to conversations the current user is actually part of.
        $owned = Conversation::where('organization_id', $this->orgId())
            ->whereIn('id', $ids)
            ->whereHas('participants', fn($q) => $q->where('user_id', $this->meId()))
            ->pluck('id')
            ->all();

        if (!empty($owned)) {
            DB::transaction(function () use ($owned) {
                Message::whereIn('conversation_id', $owned)->delete();
                DB::table('chat_conversation_user')->whereIn('conversation_id', $owned)->delete();
                Conversation::whereIn('id', $owned)->delete();
            });

            if (in_array((int) $this->conversationId, $owned, true)) {
                $this->conversationId = null;
                $this->selectedUserId = null;
            }
        }

        $this->selectedThreads   = [];
        $this->selectMode        = false;
        $this->showDeleteConfirm = false;
    }

    public function render()
    {
        $meId = $this->meId();

        // Contact list (other-panel users) with their conversation, last message and unread count.
        $contacts = $this->candidateQuery()
            ->when($this->contactSearch, fn($q) => $q->where(fn($q) => $q
                ->where('name', 'like', "%{$this->contactSearch}%")
                ->orWhere('email', 'like', "%{$this->contactSearch}%")))
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($meId) {
                $conversation = Conversation::where('organization_id', $this->orgId())
                    ->whereHas('participants', fn($q) => $q->where('user_id', $meId))
                    ->whereHas('participants', fn($q) => $q->where('user_id', $u->id))
                    ->withCount('participants')
                    ->get()
                    ->firstWhere('participants_count', 2);

                $unread = 0;
                $last   = null;
                if ($conversation) {
                    $unread = Message::where('conversation_id', $conversation->id)
                        ->where('sender_id', '!=', $meId)
                        ->whereNull('read_at')
                        ->count();
                    $last = Message::where('conversation_id', $conversation->id)->latest('id')->first();
                }

                return [
                    'user'            => $u,
                    'role_label'      => self::roleLabel($u->role),
                    'conversation_id' => $conversation?->id,
                    'unread'          => $unread,
                    'last'            => $last,
                    'last_sort'       => $last?->id ?? 0,
                ];
            })
            ->sortByDesc('last_sort')
            ->values();

        // Active conversation messages (and keep them marked read while open).
        $messages = collect();
        if ($this->conversationId) {
            $this->markRead();
            $messages = Message::where('conversation_id', $this->conversationId)
                ->orderBy('id')
                ->get();
        }

        $other = $this->selectedUserId ? User::find($this->selectedUserId) : null;

        return view('livewire.chat.messenger', [
            'contacts'        => $contacts,
            'messages'        => $messages,
            'otherUser'       => $other,
            'otherRoleLabel'  => $other ? self::roleLabel($other->role) : '',
            'myId'            => $meId,
            'panelLabel'      => 'Admins · Sub-admins · Accounts',
        ]);
    }
}
