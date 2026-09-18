<?php

namespace App\Http\Controllers\v1;

use App\Livewire\Chat\Messenger;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Messages in the admin app: the web panel's Messages (Livewire\Chat\Messenger)
 * over the same tables and by the same rules.
 *
 *  - The admins, sub-admins and accounts team of a school chat one to one, each
 *    with the active people of the other roles — an admin with sub-admins and
 *    accounts, a sub-admin with admins and accounts, and so on.
 *  - Deleting a message, or a whole chat, only hides it for whoever deleted it.
 *  - A pinned message is pinned for both people; a pinned chat only for whoever
 *    pinned it, and sorts to the top of their list.
 *  - Files stay on the server, as on the web (photos and documents, up to
 *    10 MB), and are handed out as short-lived signed links.
 *
 * Replies take the shapes of the student and teacher chat (ChatController), so
 * the app draws both with the same screens.
 *
 *   GET  /admin/chat/contacts               everyone this user can message, pinned chats and then the latest first
 *   GET  /admin/chat/with/{userId}          a conversation's messages — latest page, after_id or before_id
 *   POST /admin/chat/with/{userId}          send a message: body and/or file
 *   POST /admin/chat/messages/delete        delete messages for me       { ids: [] }
 *   POST /admin/chat/messages/pin           pin or unpin messages        { ids: [] }
 *   POST /admin/chat/messages/forward       forward messages             { ids: [], user_ids: [] }
 *   POST /admin/chat/conversations/delete   delete chats for me          { user_ids: [] }
 *   POST /admin/chat/conversations/pin      pin or unpin chats for me    { user_ids: [] }
 */
class AdminChatController extends ApiController
{
    /** The roles that share a school's Messages (the web Messenger's). */
    private const CHAT_ROLES = ['admin', 'sub-admin', 'accounts'];

    /** Messages in one page of a conversation. */
    private const PAGE = 50;

    // ══════════════════════════ ENDPOINTS ══════════════════════════

    public function contacts()
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        // As the web panel's poller does: this user now has every message sent to them.
        Message::markDeliveredFor($me->id);

        $people = $this->candidates($me)->orderBy('name')->get();
        $conversationByUser = $this->conversationsByUser($me, $people->pluck('id')->all());

        // My own pins, by conversation.
        $pinned = DB::table('chat_conversation_user')
            ->where('user_id', $me->id)
            ->whereIn('conversation_id', array_values($conversationByUser))
            ->whereNotNull('pinned_at')
            ->pluck('conversation_id')
            ->map(fn($id) => (int) $id)
            ->flip();

        $items = $people
            ->map(function (User $u) use ($me, $conversationByUser, $pinned) {
                $cid  = $conversationByUser[$u->id] ?? null;
                $last = $cid
                    ? Message::where('conversation_id', $cid)->visibleTo($me->id)->latest('id')->first()
                    : null;
                $unread = $cid
                    ? Message::where('conversation_id', $cid)->visibleTo($me->id)
                        ->where('sender_id', '!=', $me->id)->whereNull('read_at')->count()
                    : 0;

                return $this->person($u) + [
                    'conversation_id' => $cid,
                    'last_message'    => $last ? $this->preview($last, $me->id) : null,
                    'unread'          => $unread,
                    'pinned'          => $cid !== null && $pinned->has($cid),
                    '_sort'           => $last?->id ?? 0,
                ];
            })
            // Pinned chats first, then the latest conversation, then everyone else by name.
            ->sort(fn($a, $b) => [$b['pinned'], $b['_sort'], $a['name']] <=> [$a['pinned'], $a['_sort'], $b['name']])
            ->map(fn($c) => collect($c)->except('_sort')->all())
            ->values();

        return $this->success($items, 'Chat contacts fetched.');
    }

    public function messages(Request $request, int $userId)
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'after_id'  => 'nullable|integer|min:0',
            'before_id' => 'nullable|integer|min:1',
        ])) return $err;

        $other = $this->candidates($me)->find($userId);
        if (!$other) {
            return $this->error('You cannot message this person.', 403);
        }

        $conversation = $this->conversationWith($me, $userId);
        $messages = collect();
        $hasMore  = false;
        $receipts = ['delivered_up_to' => 0, 'read_up_to' => 0];
        $pinned   = collect();

        if ($conversation) {
            // Reading the conversation reads what they sent, and so delivers it.
            Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $me->id)
                ->whereNull('read_at')
                ->update([
                    'read_at'      => now(),
                    'delivered_at' => DB::raw('COALESCE(delivered_at, NOW())'),
                ]);
            $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);

            $query = Message::where('conversation_id', $conversation->id)->visibleTo($me->id);

            if ($request->filled('after_id')) {
                $messages = $query->where('id', '>', (int) $request->after_id)->orderBy('id')->limit(200)->get();
            } else {
                if ($request->filled('before_id')) {
                    $query->where('id', '<', (int) $request->before_id);
                }
                $page     = $query->orderByDesc('id')->limit(self::PAGE + 1)->get();
                $hasMore  = $page->count() > self::PAGE;
                $messages = $page->take(self::PAGE)->reverse()->values();
            }

            // How far the other person has got with mine: every message of mine
            // up to these ids is delivered / read.
            $mine = Message::where('conversation_id', $conversation->id)->where('sender_id', $me->id);
            $receipts = [
                'delivered_up_to' => (int) (clone $mine)->whereNotNull('delivered_at')->max('id'),
                'read_up_to'      => (int) (clone $mine)->whereNotNull('read_at')->max('id'),
            ];

            // Every pinned message, newest pin first, for the bar over the conversation.
            $pinned = Message::where('conversation_id', $conversation->id)
                ->visibleTo($me->id)
                ->whereNotNull('pinned_at')
                ->orderByDesc('pinned_at')
                ->get()
                ->map(fn(Message $m) => $this->format($m, $me->id))
                ->values();
        }

        return $this->success([
            'conversation_id' => $conversation?->id,
            'contact'         => $this->person($other),
            'messages'        => $messages->map(fn(Message $m) => $this->format($m, $me->id))->values(),
            'has_more'        => $hasMore,
            'receipts'        => $receipts,
            'pinned'          => $pinned,
            // Nobody is blocked in Messages.
            'blocked'         => false,
            'can_message'     => true,
        ], 'Chat messages fetched.');
    }

    public function send(Request $request, int $userId)
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        // The web panel's limits: photos and common documents, up to 10 MB.
        if ($err = $this->validateWith($request, [
            'body' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
        ], [
            'file.max'   => 'Attachment must not exceed 10 MB.',
            'file.mimes' => 'Only images and common documents are allowed.',
        ])) return $err;

        $body = trim((string) $request->input('body', ''));
        if ($body === '' && !$request->hasFile('file')) {
            return $this->error('Type a message or attach a file.', 422);
        }

        if (!$this->candidates($me)->whereKey($userId)->exists()) {
            return $this->error('You cannot message this person.', 403);
        }

        $data = [
            'sender_id' => $me->id,
            'body'      => $body !== '' ? $body : null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('chat/attachments', 's3');
            if (!$path) {
                return $this->error('The file could not be uploaded. Please try again.', 500);
            }

            $mime = (string) $file->getMimeType();
            // The object stays private on the bucket; format() signs a link to it.
            $data['attachment_url']  = Storage::disk('s3')->url($path);
            $data['attachment_path'] = $path;
            $data['attachment_name'] = $file->getClientOriginalName();
            $data['attachment_type'] = str_starts_with($mime, 'image/') ? 'image' : 'file';
            $data['attachment_size'] = (int) $file->getSize();
        }

        $conversation = $this->conversationWith($me, $userId) ?? $this->startConversation($me, $userId);

        $message = Message::create($data + ['conversation_id' => $conversation->id]);
        $conversation->update(['last_message_at' => now()]);

        // Sending re-opens a chat the sender had cleared for themselves.
        $conversation->participants()->updateExistingPivot($me->id, ['cleared_at' => null]);

        return $this->success($this->format($message, $me->id), 'Message sent.', 201);
    }

    public function deleteMessages(Request $request)
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ])) return $err;

        $ids = $this->myMessages($me, $request->ids)->pluck('id')->all();

        $this->hideForMe($me->id, $ids);

        return $this->success(['deleted' => count($ids)], 'Messages deleted.');
    }

    /** Pin the selected messages — or unpin them, when every one is pinned already. */
    public function pinMessages(Request $request)
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ])) return $err;

        $messages = $this->myMessages($me, $request->ids);
        if ($messages->isEmpty()) {
            return $this->error('Nothing to pin.', 404);
        }

        $unpin = $messages->every(fn(Message $m) => $m->pinned_at !== null);
        Message::whereIn('id', $messages->pluck('id'))->update(['pinned_at' => $unpin ? null : now()]);

        return $this->success(
            ['pinned' => !$unpin, 'ids' => $messages->pluck('id')->values()],
            $unpin ? 'Unpinned.' : 'Pinned.'
        );
    }

    /** Send copies of the selected messages, files included, to other people. */
    public function forwardMessages(Request $request)
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer',
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ])) return $err;

        $messages = $this->myMessages($me, $request->ids);
        if ($messages->isEmpty()) {
            return $this->error('Nothing to forward.', 404);
        }

        $targets = $this->candidates($me)
            ->whereIn('id', array_map('intval', $request->user_ids))
            ->pluck('id')
            ->map(fn($id) => (int) $id);
        if ($targets->isEmpty()) {
            return $this->error('You cannot message these people.', 403);
        }

        DB::transaction(function () use ($me, $messages, $targets) {
            foreach ($targets as $userId) {
                $conversation = $this->conversationWith($me, $userId) ?? $this->startConversation($me, $userId);

                foreach ($messages as $m) {
                    Message::create([
                        'conversation_id'   => $conversation->id,
                        'sender_id'         => $me->id,
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
                $conversation->participants()->updateExistingPivot($me->id, ['cleared_at' => null]);
            }
        });

        return $this->success(['forwarded_to' => $targets->count()], 'Forwarded.');
    }

    /** Clear chats for me only; the other person's copy stays. */
    public function deleteConversations(Request $request)
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ])) return $err;

        $conversationIds = array_values($this->conversationsByUser($me, array_map('intval', $request->user_ids)));

        if ($conversationIds) {
            $messageIds = Message::whereIn('conversation_id', $conversationIds)->pluck('id')->all();

            DB::transaction(function () use ($me, $conversationIds, $messageIds) {
                $this->hideForMe($me->id, $messageIds);
                DB::table('chat_conversation_user')
                    ->whereIn('conversation_id', $conversationIds)
                    ->where('user_id', $me->id)
                    ->update(['cleared_at' => now()]);
            });
        }

        return $this->success(['deleted' => count($conversationIds)], 'Chats deleted.');
    }

    /** Pin chats for me — or unpin them, when every one is pinned already. */
    public function pinConversations(Request $request)
    {
        [$me, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ])) return $err;

        $byUser = $this->conversationsByUser($me, array_map('intval', $request->user_ids));
        if (!$byUser) {
            return $this->error('Nothing to pin.', 404);
        }

        $mine = fn() => DB::table('chat_conversation_user')
            ->where('user_id', $me->id)
            ->whereIn('conversation_id', array_values($byUser));

        $unpin = $mine()->whereNull('pinned_at')->doesntExist();
        $mine()
            ->when(!$unpin, fn($q) => $q->whereNull('pinned_at'))
            ->update(['pinned_at' => $unpin ? null : now(), 'updated_at' => now()]);

        return $this->success(
            ['pinned' => !$unpin, 'user_ids' => array_keys($byUser)],
            $unpin ? 'Unpinned.' : 'Pinned.'
        );
    }

    // ══════════════════════════ WHO CAN CHAT ══════════════════════════

    private function guard(): array
    {
        [$me, $err] = $this->authUser();
        if ($err) return [null, $err];

        if (!in_array($me->role, self::CHAT_ROLES, true) || !$me->organization_id) {
            return [null, $this->error('Messages are only for the school\'s admins, sub-admins and accounts team.', 403)];
        }

        return [$me, null];
    }

    /**
     * The people $me may message, as on the web: everyone active in the same
     * school whose role is admin, sub-admin or accounts, but not $me's own.
     */
    private function candidates(User $me): Builder
    {
        return User::where('organization_id', $me->organization_id)
            ->where('is_active', 1)
            ->whereIn('role', self::CHAT_ROLES)
            ->where('role', '!=', $me->role)
            ->where('id', '!=', $me->id);
    }

    /** A person as the app shows them: name, photo and their role ("Sub-admin"). */
    private function person(User $u): array
    {
        return [
            'user_id'  => (int) $u->id,
            'name'     => (string) $u->name,
            'avatar'   => $u->image,
            'subtitle' => Messenger::roleLabel($u->role),
            'role'     => $u->role,
        ];
    }

    // ══════════════════════════ CONVERSATIONS ══════════════════════════

    /** [other user id => conversation id] for $me's one-to-one conversations with these users. */
    private function conversationsByUser(User $me, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $mine = DB::table('chat_conversation_user as cu')
            ->join('chat_conversations as c', 'c.id', '=', 'cu.conversation_id')
            ->where('cu.user_id', $me->id)
            ->where('c.organization_id', $me->organization_id)
            ->pluck('cu.conversation_id');

        if ($mine->isEmpty()) {
            return [];
        }

        $pairs = DB::table('chat_conversation_user')
            ->whereIn('conversation_id', $mine)
            ->groupBy('conversation_id')
            ->havingRaw('COUNT(*) = 2')
            ->pluck('conversation_id');

        return DB::table('chat_conversation_user')
            ->whereIn('conversation_id', $pairs)
            ->where('user_id', '!=', $me->id)
            ->whereIn('user_id', $userIds)
            ->pluck('conversation_id', 'user_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function conversationWith(User $me, int $userId): ?Conversation
    {
        $id = $this->conversationsByUser($me, [$userId])[$userId] ?? null;

        return $id ? Conversation::find($id) : null;
    }

    private function startConversation(User $me, int $userId): Conversation
    {
        $conversation = Conversation::create([
            'organization_id' => $me->organization_id,
            'last_message_at' => now(),
        ]);
        $conversation->participants()->attach([$me->id, $userId]);

        return $conversation;
    }

    /** $me's visible messages among these ids, in this school's conversations $me belongs to. */
    private function myMessages(User $me, array $ids)
    {
        return Message::whereIn('id', array_map('intval', $ids))
            ->visibleTo($me->id)
            ->whereHas('conversation', fn($q) => $q
                ->where('organization_id', $me->organization_id)
                ->whereHas('participants', fn($p) => $p->where('user_id', $me->id)))
            ->orderBy('id')
            ->get();
    }

    /** Record "deleted for me" rows, skipping any that already exist. */
    private function hideForMe(int $userId, array $messageIds): void
    {
        if (empty($messageIds)) {
            return;
        }

        $already = DB::table('chat_message_deletes')
            ->where('user_id', $userId)
            ->whereIn('message_id', $messageIds)
            ->pluck('message_id')
            ->all();

        $now  = now();
        $rows = collect($messageIds)
            ->diff($already)
            ->map(fn($id) => ['message_id' => (int) $id, 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('chat_message_deletes')->insert($chunk);
        }
    }

    // ══════════════════════════ OUTPUT ══════════════════════════

    /** 'image' | 'file' — the web panel sends nothing else. */
    private function attachmentType(Message $m): string
    {
        return $m->attachment_type === 'image' ? 'image' : 'file';
    }

    private function format(Message $m, int $meId): array
    {
        $hasFile = $m->attachment_path || $m->attachment_url;

        return [
            'id'         => $m->id,
            'body'       => $m->body,
            'mine'       => (int) $m->sender_id === $meId,
            'created_at' => $m->created_at?->toIso8601String(),
            'status'     => $m->deliveryState(),
            'pinned'     => $m->pinned_at !== null,
            'forwarded'  => $m->forwarded_from_id !== null,
            'attachment' => $hasFile ? [
                'type' => $this->attachmentType($m),
                'name' => $m->attachment_name,
                'size' => $m->attachment_size ? (int) $m->attachment_size : null,
                'url'  => $this->attachmentUrl($m),
            ] : null,
        ];
    }

    private function preview(Message $m, int $meId): array
    {
        $hasFile = $m->attachment_path || $m->attachment_url;

        return [
            'body'            => $m->body,
            'attachment_type' => $hasFile ? $this->attachmentType($m) : null,
            'mine'            => (int) $m->sender_id === $meId,
            'created_at'      => $m->created_at?->toIso8601String(),
        ];
    }

    /** A signed link good for an hour — the bucket keeps chat files private. */
    private function attachmentUrl(Message $m): ?string
    {
        if ($m->attachment_path) {
            try {
                return Storage::disk('s3')->temporaryUrl(ltrim($m->attachment_path, '/'), now()->addHour());
            } catch (\Throwable $e) {
                // Signing unavailable — fall back to the stored URL.
            }
        }

        return $m->attachment_url;
    }
}
