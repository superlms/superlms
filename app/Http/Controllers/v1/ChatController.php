<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\TeacherTimeTable;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Chat between students and their teachers, for the mobile app.
 *
 * It shares the web panel's chat tables and rules: one-to-one conversations
 * inside an organization, deletes that only hide things for whoever deleted
 * them, delivered and read receipts, and private S3 attachments handed out as
 * short-lived signed links. Who may talk to whom follows the timetable:
 *
 *  - a student (role user) chats with the teachers who teach their class and
 *    section;
 *  - a teacher chats with the students of every class and section they teach.
 *
 * Messages reach an open conversation in real time: the app checks it every
 * few seconds while it is on screen, and a data-only push (type chat_message)
 * reaches the recipient the moment a message is sent.
 *
 *   GET  /chat/contacts               everyone this user can chat with, latest conversation first
 *   GET  /chat/with/{userId}          a conversation's messages — latest page, after_id or before_id
 *   POST /chat/with/{userId}          send a message: body and/or file
 *   POST /chat/messages/delete        delete messages for me    { ids: [] }
 *   POST /chat/conversations/delete   delete chats for me       { user_ids: [] }
 */
class ChatController extends ApiController
{
    /** Messages in one page of a conversation. */
    private const PAGE = 50;

    // ══════════════════════════ ENDPOINTS ══════════════════════════

    public function contacts()
    {
        [$me, $err] = $this->authUser();
        if ($err) return $err;

        $people = $this->people($me);
        if ($people === null) {
            return $this->error('Chat is only for students and their teachers.', 403);
        }

        // The app now has every message sent to this user: the senders' second tick.
        Message::markDeliveredFor($me->id);

        $rows = $people->get()->map(fn($p) => $this->person($p));
        $conversationByUser = $this->conversationsByUser($me, $rows->pluck('user_id')->all());

        $items = $rows
            ->map(function (array $p) use ($me, $conversationByUser) {
                $cid  = $conversationByUser[$p['user_id']] ?? null;
                $last = $cid
                    ? Message::where('conversation_id', $cid)->visibleTo($me->id)->latest('id')->first()
                    : null;
                $unread = $cid
                    ? Message::where('conversation_id', $cid)->visibleTo($me->id)
                        ->where('sender_id', '!=', $me->id)->whereNull('read_at')->count()
                    : 0;

                return $p + [
                    'conversation_id' => $cid,
                    'last_message'    => $last ? $this->preview($last, $me->id) : null,
                    'unread'          => $unread,
                    '_sort'           => $last?->id ?? 0,
                ];
            })
            // The latest conversation first, then everyone else by name.
            ->sort(fn($a, $b) => [$b['_sort'], $a['name']] <=> [$a['_sort'], $b['name']])
            ->map(fn($c) => collect($c)->except('_sort')->all())
            ->values();

        return $this->success($items, 'Chat contacts fetched.');
    }

    public function messages(Request $request, int $userId)
    {
        [$me, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'after_id'  => 'nullable|integer|min:0',
            'before_id' => 'nullable|integer|min:1',
        ])) return $err;

        $contact = $this->personFor($me, $userId);
        if (!$contact) {
            return $this->error('You cannot chat with this person.', 403);
        }

        $conversation = $this->conversationWith($me, $userId);
        $messages = collect();
        $hasMore  = false;
        $receipts = ['delivered_up_to' => 0, 'read_up_to' => 0];

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
        }

        return $this->success([
            'conversation_id' => $conversation?->id,
            'contact'         => $contact,
            'messages'        => $messages->map(fn(Message $m) => $this->format($m, $me->id))->values(),
            'has_more'        => $hasMore,
            'receipts'        => $receipts,
        ], 'Chat messages fetched.');
    }

    public function send(Request $request, int $userId)
    {
        [$me, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'body' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,webp,gif,heic,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
        ])) return $err;

        $body = trim((string) $request->input('body', ''));
        if ($body === '' && !$request->hasFile('file')) {
            return $this->error('Type a message or attach a file.', 422);
        }

        if (!$this->personFor($me, $userId)) {
            return $this->error('You cannot chat with this person.', 403);
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

        // Sending brings back a chat the sender had deleted for themselves.
        $conversation->participants()->updateExistingPivot($me->id, ['cleared_at' => null]);

        $this->pushTo($me, $userId, $message);

        return $this->success($this->format($message, $me->id), 'Message sent.', 201);
    }

    public function deleteMessages(Request $request)
    {
        [$me, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ])) return $err;

        $ids = Message::whereIn('id', $request->ids)
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $me->id))
            ->pluck('id')
            ->all();

        $this->hideForMe($me->id, $ids);

        return $this->success(['deleted' => count($ids)], 'Messages deleted.');
    }

    public function deleteConversations(Request $request)
    {
        [$me, $err] = $this->authUser();
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

    // ══════════════════════════ WHO CAN CHAT ══════════════════════════

    /** The people $me may chat with, as a query — null for anyone but a student or teacher. */
    private function people(User $me): ?Builder
    {
        return match ($me->role) {
            'user'    => $this->teachersOfStudent($me),
            'teacher' => $this->studentsOfTeacher($me),
            default   => null,
        };
    }

    /** One of $me's contacts by their user id, or null if they are not one. */
    private function personFor(User $me, int $userId): ?array
    {
        $found = $this->people($me)?->where('user_id', $userId)->first();

        return $found ? $this->person($found) : null;
    }

    /** The teachers whose timetable has the student's class and section. */
    private function teachersOfStudent(User $me): Builder
    {
        $student = StudentDetail::where('user_id', $me->id)->first(['standard_id', 'section_id']);

        $inClass = fn($q) => $q
            ->where('standard_id', $student?->standard_id)
            ->where('section_id', $student?->section_id)
            ->where('is_active', true);

        return TeacherDetail::query()
            ->with([
                'user:id,name,image,is_active',
                'timetables' => fn($q) => $inClass($q)->with('subject:id,name'),
            ])
            ->where('organization_id', $me->organization_id)
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->whereHas('timetables', $inClass)
            ->when(!$student, fn($q) => $q->whereRaw('0 = 1'));
    }

    /** The students of every class and section in the teacher's timetable. */
    private function studentsOfTeacher(User $me): Builder
    {
        $teacher = TeacherDetail::where('user_id', $me->id)->first(['id']);

        $classes = $teacher
            ? TeacherTimeTable::where('teacher_detail_id', $teacher->id)
                ->where('organization_id', $me->organization_id)
                ->where('is_active', true)
                ->get(['standard_id', 'section_id'])
                ->unique(fn($p) => $p->standard_id . ':' . $p->section_id)
            : collect();

        return StudentDetail::query()
            ->with(['user:id,name,image,is_active', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $me->organization_id)
            ->whereNotNull('user_id')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->where(function ($q) use ($classes) {
                if ($classes->isEmpty()) {
                    $q->whereRaw('0 = 1');
                }
                foreach ($classes as $c) {
                    $q->orWhere(fn($w) => $w->where('standard_id', $c->standard_id)->where('section_id', $c->section_id));
                }
            })
            ->orderBy('full_name');
    }

    /** A contact as the app shows them: name, photo and what they are to this user. */
    private function person(TeacherDetail|StudentDetail $p): array
    {
        if ($p instanceof TeacherDetail) {
            return [
                'user_id'  => (int) $p->user_id,
                'name'     => (string) $p->user?->name,
                'avatar'   => $p->user?->image,
                // What they teach the student's class: "Mathematics, Science".
                'subtitle' => $p->timetables->pluck('subject.name')->filter()->unique()->implode(', ') ?: null,
            ];
        }

        return [
            'user_id'  => (int) $p->user_id,
            'name'     => (string) ($p->full_name ?: $p->user?->name),
            'avatar'   => $p->user?->image,
            // "10th A"
            'subtitle' => trim(($p->standard?->name ?? '') . ' ' . ($p->section?->name ?? '')) ?: null,
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

    private function format(Message $m, int $meId): array
    {
        $hasFile = $m->attachment_path || $m->attachment_url;

        return [
            'id'         => $m->id,
            'body'       => $m->body,
            'mine'       => (int) $m->sender_id === $meId,
            'created_at' => $m->created_at?->toIso8601String(),
            'status'     => $m->deliveryState(),
            'attachment' => $hasFile ? [
                'type' => $m->attachment_type === 'image' ? 'image' : 'file',
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
            'attachment_type' => $hasFile ? ($m->attachment_type === 'image' ? 'image' : 'file') : null,
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

    /**
     * Tell the recipient's phones, once the response is on its way so sending
     * never waits on FCM. The push carries the sender as the recipient sees
     * them, so tapping it opens the conversation.
     */
    private function pushTo(User $me, int $userId, Message $message): void
    {
        $recipient = User::find($userId);
        if (!$recipient) {
            return;
        }

        $sender = $this->personFor($recipient, $me->id)
            ?? ['user_id' => $me->id, 'name' => $me->name, 'avatar' => $me->image, 'subtitle' => null];

        $preview = $message->body
            ? Str::limit($message->body, 120)
            : ($message->attachment_type === 'image' ? 'Sent a photo' : 'Sent a file');

        app()->terminating(function () use ($recipient, $sender, $preview) {
            try {
                app(FirebaseNotificationService::class)->notifyUser($recipient, 'chat_message', [
                    'title'  => $sender['name'],
                    'body'   => $preview,
                    'screen' => 'UserChats',
                    'params' => [
                        'contact'  => $sender,
                        'userRole' => $recipient->role === 'teacher' ? 'teacher' : 'student',
                    ],
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
