<?php

namespace App\Support;

use App\Models\Admin\TransportFeePayment;
use App\Models\Chat\Message;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Models\Student\Standard;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * In-app notifications for the accounts desk.
 *
 * The accounts panel carries the same money screens the admin panel does —
 * fee structure, fee cycle, fee submission, concession — so a change made from
 * EITHER side has to reach the accountants: someone looking at a fee cycle
 * needs to know the admin just re-balanced it, and someone reconciling the day
 * needs to know a payment landed online while they weren't looking.
 *
 * Two things make this its own class rather than more rules inside
 * {@see ActivityNotifier}:
 *
 *  - **One notification per action, not per row.** Saving a fee structure
 *    writes a row per fee head and re-generating a monthly cycle writes twelve;
 *    the fee desk wants "Fee cycle re-balanced — 4 installments, 100%", not
 *    twelve separate lines.
 *  - **Most of these writes never fire a model event.** Editing a cycle, a
 *    concession or deleting a fee-structure group all go through the query
 *    builder, which Eloquent's global `updated`/`deleted` listeners never see.
 *
 * ActivityNotifier keeps feeding the admin's row-level activity feed exactly as
 * before — nothing here changes what admins receive.
 *
 * Every call is wrapped so a notification failure can NEVER break the write
 * that triggered it.
 */
class AccountsNotifier
{
    protected static array $roleLabels = [
        'admin'       => 'Admin',
        'sub-admin'   => 'Sub-admin',
        'accounts'    => 'Accountant',
        'teacher'     => 'Teacher',
        'user'        => 'Student',
        'super_admin' => 'Super Admin',
    ];

    // ── Fee Structure ───────────────────────────────────────────────────────

    /**
     * A whole class+section fee structure was saved or removed. `$where` names
     * the group it belongs to — build it with {@see static::classLabel()}.
     *
     * @param  string  $action  added | updated | deleted
     */
    public static function feeStructure(string $action, ?int $orgId, string $where, int $rows, float $total): void
    {
        $money = $total > 0 ? ' · ₹' . number_format($total, 2) : '';

        static::send(
            'fee_structure',
            'Fee structure ' . $action,
            "Fee structure for {$where} was {$action} — {$rows} fee head" . ($rows === 1 ? '' : 's') . $money,
            $orgId
        );
    }

    // ── Fee Cycle ───────────────────────────────────────────────────────────

    /**
     * The installment plan changed. `$detail` says what the cycle now looks
     * like, e.g. "Quarterly · academic · 2026-27 · 4 installments · 100%".
     */
    public static function feeCycle(string $action, ?int $orgId, string $detail): void
    {
        static::send(
            'fee_cycle',
            'Fee cycle ' . $action,
            "Fee cycle {$action} — {$detail}",
            $orgId
        );
    }

    // ── Fee submission ──────────────────────────────────────────────────────

    /**
     * Money came in — from the fee counter, the admin panel, or a student
     * paying online. The online path runs from a payment callback with nobody
     * logged in, so the payment row itself carries the "who".
     */
    public static function feePayment(Model $payment): void
    {
        $student = static::studentName($payment->student_detail_id ?? null);
        $amount  = '₹' . number_format((float) ($payment->amount ?? 0), 2);
        $mode    = Str::headline((string) ($payment->payment_mode ?? 'cash'));
        $kind    = $payment instanceof TransportFeePayment ? 'Transport fee' : Str::ucfirst((string) ($payment->fee_type ?? 'Fee')) . ' fee';
        $receipt = $payment->receipt_number ?? null;

        $body = "{$kind} of {$amount} received from {$student} ({$mode})";
        if ($receipt) {
            $body .= " — receipt {$receipt}";
        }

        static::send(
            'fee_payment',
            'Fee received · ' . $amount,
            $body,
            (int) ($payment->organization_id ?? 0) ?: null
        );
    }

    // ── Concession ──────────────────────────────────────────────────────────

    /** A student's fee was discounted (or that discount was changed). */
    public static function concession(string $action, ?int $orgId, $studentDetailId, string $type, $value, string $feeType, string $reason = ''): void
    {
        $student = static::studentName($studentDetailId);
        $amount  = $type === 'percent'
            ? rtrim(rtrim(number_format((float) $value, 2), '0'), '.') . '%'
            : '₹' . number_format((float) $value, 2);

        $body = "{$student} was given a {$amount} concession on {$feeType} fee";
        if (trim($reason) !== '') {
            $body .= ' — ' . Str::limit(trim($reason), 60);
        }

        static::send(
            'concession',
            'Concession ' . $action,
            $body,
            $orgId
        );
    }

    // ── Messages ────────────────────────────────────────────────────────────

    /**
     * A chat message reaches the recipients' bell as well as the live toast —
     * the toast only exists while a browser is open, the bell is what you read
     * when you come back. The sender is never notified of their own message.
     */
    public static function chatMessage(Message $message): void
    {
        try {
            $recipients = User::query()
                ->whereIn('id', function ($q) use ($message) {
                    $q->select('user_id')
                        ->from('chat_conversation_user')
                        ->where('conversation_id', $message->conversation_id);
                })
                ->where('id', '!=', $message->sender_id)
                ->get();

            // One bell entry per conversation, not per message: if the last one
            // is still unread there is nothing new to tell them, and a busy
            // thread would otherwise bury everything else in the bell.
            $recipients = $recipients->filter(fn (User $u) => !$u->unreadNotifications()
                ->where('data', 'like', '%"conversation_id":' . (int) $message->conversation_id . '%')
                ->exists());

            if ($recipients->isEmpty()) {
                return;
            }

            $sender = $message->sender ?: User::find($message->sender_id);
            $name   = $sender?->name ?: 'Someone';
            $role   = static::$roleLabels[$sender?->role] ?? Str::headline((string) $sender?->role);

            Notification::send($recipients, new ActivityNotification([
                'type'            => 'message',
                'title'           => "New message from {$name}",
                'body'            => "{$name} ({$role}): " . static::messagePreview($message),
                'actor_id'        => $sender?->id,
                'actor_name'      => $name,
                'actor_role'      => $sender?->role,
                'subject_type'    => Message::class,
                'subject_id'      => $message->id,
                'conversation_id' => (int) $message->conversation_id,
                'organization_id' => $sender?->organization_id,
                'at'              => now()->toIso8601String(),
            ]));
        } catch (\Throwable $e) {
            logger()->warning('AccountsNotifier chat message failed: ' . $e->getMessage());
        }
    }

    protected static function messagePreview(Message $message): string
    {
        if (trim((string) $message->body) !== '') {
            return Str::limit($message->body, 100);
        }
        if ($message->attachment_type === 'image') {
            return '📷 Photo';
        }
        if ($message->attachment_type) {
            return '📎 ' . ($message->attachment_name ?: 'Document');
        }
        return 'New message';
    }

    // ── Plumbing ────────────────────────────────────────────────────────────

    /** Deliver one notification to every accountant of the organization. */
    protected static function send(string $type, string $title, string $body, ?int $orgId): void
    {
        try {
            $actor = Auth::user();

            // Nobody logged in on the console means a seeder, a migration or an
            // artisan command — not something the fee desk needs to hear about.
            // An online payment settling from a gateway callback has no actor
            // either, but that runs over HTTP, so it still gets through.
            if (!$actor && app()->runningInConsole()) {
                return;
            }

            $orgId = $orgId ?: (int) ($actor->organization_id ?? 0);
            if (!$orgId) {
                return;
            }

            $recipients = User::query()
                ->where('role', 'accounts')
                ->where('organization_id', $orgId)
                ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new ActivityNotification([
                'type'            => $type,
                'title'           => $title,
                'body'            => static::withActor($body, $actor),
                'actor_id'        => $actor?->id,
                'actor_name'      => $actor?->name,
                'actor_role'      => $actor?->role,
                'organization_id' => $orgId,
                'at'              => now()->toIso8601String(),
            ]));
        } catch (\Throwable $e) {
            // A notification must never break the action that triggered it.
            logger()->warning('AccountsNotifier failed: ' . $e->getMessage());
        }
    }

    /** "… — by Ramesh (Admin)." so the desk can see who moved the number. */
    protected static function withActor(string $body, $actor): string
    {
        $body = rtrim($body, '.');

        if (!$actor) {
            // Online payments settle from a payment callback with nobody logged in.
            return $body . '.';
        }

        $role = static::$roleLabels[$actor->role] ?? Str::headline((string) $actor->role);

        return $body . ' — by ' . ($actor->name ?: 'Someone') . " ({$role}).";
    }

    /** "Class 5 · A", or "Class 5 · All Sections" when the group has no section. */
    public static function classLabel($standardId, $sectionId): string
    {
        $standard = $standardId ? Standard::find($standardId) : null;
        $section  = $sectionId ? Section::find($sectionId) : null;

        $label = $standard?->name ? 'Class ' . $standard->name : 'this class';

        return $label . ' · ' . ($section?->name ?: 'All Sections');
    }

    protected static function studentName($studentDetailId): string
    {
        if (!$studentDetailId) {
            return 'a student';
        }

        $student = StudentDetail::find($studentDetailId);

        return $student?->full_name ?: 'a student';
    }
}
