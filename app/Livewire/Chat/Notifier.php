<?php

namespace App\Livewire\Chat;

use App\Models\Chat\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Tiny global poller mounted on every authenticated page for chat roles.
 * Every few seconds it pushes the live unread count + any brand-new incoming
 * messages to the browser via the `chat-sync` event. The navbar badge and the
 * WhatsApp-style toast previews both listen for that event.
 */
class Notifier extends Component
{
    public int $lastSeenId = 0;
    public int $unread     = 0;

    protected const CHAT_ROLES = ['admin', 'sub-admin', 'accounts'];

    protected function eligible(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, self::CHAT_ROLES, true);
    }

    public function mount(): void
    {
        if (!$this->eligible()) {
            return;
        }

        // Guarded so a missing chat table can never break the global layout.
        try {
            $meId = (int) Auth::id();
            // Seed to the newest existing id so we never toast historical messages.
            $this->lastSeenId = Message::latestIdFor($meId);
            $this->unread     = Message::unreadCountFor($meId);
        } catch (\Throwable $e) {
            $this->lastSeenId = 0;
            $this->unread     = 0;
        }
    }

    public function poll(): void
    {
        if (!$this->eligible()) {
            return;
        }

        try {
            $meId = (int) Auth::id();

            $new    = Message::newIncomingFor($meId, $this->lastSeenId);
            $toasts = [];

            if ($new->isNotEmpty()) {
                $this->lastSeenId = (int) $new->max('id');

                $toasts = $new->map(fn(Message $m) => [
                    'id'      => $m->id,
                    'name'    => $m->sender?->name ?? 'New message',
                    'image'   => $m->sender?->image,
                    'role'    => Messenger::roleLabel($m->sender?->role),
                    'preview' => $this->preview($m),
                    'time'    => optional($m->created_at)->format('h:i A'),
                ])->values()->all();
            }

            $this->unread = Message::unreadCountFor($meId);

            $this->dispatch('chat-sync', unread: $this->unread, toasts: $toasts);
        } catch (\Throwable $e) {
            // Stay silent — never break the page over a chat poll.
        }
    }

    protected function preview(Message $m): string
    {
        if ($m->body) {
            return Str::limit($m->body, 80);
        }
        if ($m->attachment_type === 'image') {
            return '📷 Photo';
        }
        if ($m->attachment_type) {
            return '📎 ' . ($m->attachment_name ?: 'Document');
        }
        return 'New message';
    }

    protected function messagesUrl(): string
    {
        $user = Auth::user();
        if (!$user) {
            return '#';
        }
        if ($user->role === 'accounts') {
            return route('accounts.messages', ['organization' => $user->organization_id]);
        }
        return route('admin.messages', ['organization' => $user->organization_id]);
    }

    public function render()
    {
        return view('livewire.chat.notifier', [
            'messagesUrl' => $this->messagesUrl(),
        ]);
    }
}
