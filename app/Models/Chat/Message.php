<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'forwarded_from_id',
        'body',
        'attachment_url',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_size',
        'read_at',
        'delivered_at',
        'pinned_at',
    ];

    protected $casts = [
        'read_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'pinned_at'    => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** Hide messages this user deleted for themselves (the other side keeps them). */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->whereNotExists(fn($q) => $q
            ->select(DB::raw(1))
            ->from('chat_message_deletes')
            ->whereColumn('chat_message_deletes.message_id', 'chat_messages.id')
            ->where('chat_message_deletes.user_id', $userId));
    }

    /** Delivery state for the sender's ticks: 'sent' | 'delivered' | 'read'. */
    public function deliveryState(): string
    {
        if ($this->read_at) {
            return 'read';
        }
        return $this->delivered_at ? 'delivered' : 'sent';
    }

    /** Count of unread incoming messages across all of the user's conversations. */
    public static function unreadCountFor(int $userId): int
    {
        return static::query()
            ->visibleTo($userId)
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $userId))
            ->count();
    }

    /** Highest message id visible to the user (used to seed the notifier). */
    public static function latestIdFor(int $userId): int
    {
        return (int) static::query()
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $userId))
            ->max('id');
    }

    /** Incoming messages newer than $afterId, for live toast notifications. */
    public static function newIncomingFor(int $userId, int $afterId)
    {
        return static::query()
            ->with(['sender:id,name,image,role'])
            ->visibleTo($userId)
            ->where('id', '>', $afterId)
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $userId))
            ->orderBy('id')
            ->get();
    }

    /**
     * The recipient's session has seen these messages exist — give the sender
     * the second tick. Called from the global poller, so "delivered" means
     * "their browser has the message", exactly like a chat app.
     */
    public static function markDeliveredFor(int $userId): void
    {
        static::query()
            ->whereNull('delivered_at')
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $userId))
            ->update(['delivered_at' => now()]);
    }
}
