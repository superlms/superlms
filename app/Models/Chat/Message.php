<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachment_url',
        'attachment_name',
        'attachment_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** Count of unread incoming messages across all of the user's conversations. */
    public static function unreadCountFor(int $userId): int
    {
        return static::query()
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
            ->where('id', '>', $afterId)
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $userId))
            ->orderBy('id')
            ->get();
    }
}
