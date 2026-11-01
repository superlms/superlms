<?php

namespace App\Services;

use App\Models\Messaging\EmailMessage;
use App\Models\Messaging\MessagingSchoolSetting;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Email a school sends through Messaging (a notice to its students and
 * teachers), at the email rate from its wallet (MessagingWallet). Off for
 * every school until the Super Admin turns it on (Messaging → Email). Sent by
 * ZeptoMail with the school's name as the sender's; every email is a row in
 * email_messages — a failed one is not charged.
 */
class SchoolEmail
{
    /** Whether the tables are there yet (the migration of 1 Nov 2026) — asked once a request. */
    public static function tracked(): bool
    {
        $key = 'email_messages.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('email_messages') && Schema::hasTable('messaging_school_settings');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    /** Whether ZeptoMail is set up on the server. */
    public static function configured(): bool
    {
        return (bool) config('services.zeptomail.api_token');
    }

    /** Whether a school may send email from Messaging (off until the Super Admin turns it on). */
    public static function enabled(?int $orgId): bool
    {
        if (!$orgId || !self::tracked()) {
            return false;
        }

        return (bool) MessagingSchoolSetting::where('organization_id', $orgId)->value('email_enabled');
    }

    public static function setEnabled(int $orgId, bool $on): void
    {
        if (!self::tracked()) {
            return;
        }

        MessagingSchoolSetting::updateOrCreate(['organization_id' => $orgId], ['email_enabled' => $on]);
    }

    /**
     * Send one email and keep its row. A dedupe key already used means it has
     * gone (or is going) — nothing is sent twice. Returns 'sent', 'failed' or
     * 'skipped' (no address, a dedupe key already used, the wallet short).
     */
    public static function send(int $orgId, ?string $email, ?string $name, string $subject, string $body, array $who = [], ?int $campaignId = null, ?string $dedupeKey = null): string
    {
        $email = trim((string) $email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !self::tracked()) {
            return 'skipped';
        }

        $rate = MessagingWallet::emailRate();
        if (!MessagingWallet::canSpend($orgId, $rate)) {
            Log::info('Messaging email: wallet short, not sent', ['org' => $orgId, 'campaign' => $campaignId]);
            return 'skipped';
        }

        try {
            $row = EmailMessage::create([
                'organization_id'   => $orgId,
                'campaign_id'       => $campaignId,
                'user_id'           => $who['user_id'] ?? null,
                'student_detail_id' => $who['student_detail_id'] ?? null,
                'to_email'          => mb_substr($email, 0, 255),
                'subject'           => mb_substr($subject, 0, 255),
                'dedupe_key'        => $dedupeKey,
                'status'            => 'sending',
                'billable'          => $rate > 0,
                'rate'              => $rate,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($dedupeKey !== null && EmailMessage::where('dedupe_key', $dedupeKey)->exists()) {
                return 'skipped';
            }
            throw $e;
        }

        $school = Organization::whereKey($orgId)->value('name') ?? 'Your school';

        try {
            ZeptoMailService::sendRaw(
                $subject,
                self::html($school, $subject, $body, $name),
                $email,
                $name ?: null,
                null,
                $school,
            );
            $row->forceFill(['status' => 'sent', 'error' => null])->save();

            return 'sent';
        } catch (\Throwable $e) {
            $row->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            return 'failed';
        }
    }

    /** The email: the school's name over the message, the subject as its title. */
    public static function html(string $school, string $subject, string $body, ?string $name = null): string
    {
        $e     = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $lines = nl2br($e($body));
        $hello = $name ? '<p style="margin:0 0 14px;color:#374151;">Dear ' . $e($name) . ',</p>' : '';

        return '<!doctype html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">'
            . '<table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">'
            . '<tr><td style="padding:20px 24px;border-bottom:1px solid #f3f4f6;">'
            . '<div style="font-size:16px;font-weight:bold;color:#111827;">' . $e($school) . '</div>'
            . '<div style="font-size:13px;color:#6b7280;margin-top:4px;">' . $e($subject) . '</div></td></tr>'
            . '<tr><td style="padding:22px 24px;font-size:14px;line-height:1.6;color:#111827;">' . $hello . $lines . '</td></tr>'
            . '<tr><td style="padding:14px 24px;border-top:1px solid #f3f4f6;font-size:11px;color:#9ca3af;">'
            . 'Sent by ' . $e($school) . ' through SuperLMS.</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /**
     * What Messaging → Email shows of a school: on / off, this month's emails
     * and what they came to, and the latest.
     */
    public static function summary(int $orgId, int $latest = 25): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $base       = fn () => EmailMessage::where('organization_id', $orgId);

        $rows     = self::tracked() ? $base()->latest('id')->limit($latest)->get() : collect();
        $students = StudentDetail::whereIn('id', $rows->pluck('student_detail_id')->filter()->unique())->pluck('full_name', 'id');
        $users    = User::whereIn('id', $rows->pluck('user_id')->filter()->unique())->pluck('name', 'id');

        return [
            'enabled'    => self::enabled($orgId),
            'configured' => self::configured(),
            'rate'       => MessagingWallet::emailRate(),
            'month'      => [
                'sent'   => self::tracked() ? $base()->where('status', 'sent')->where('created_at', '>=', $monthStart)->count() : 0,
                'failed' => self::tracked() ? $base()->where('status', 'failed')->where('created_at', '>=', $monthStart)->count() : 0,
                'amount' => self::tracked() ? round((float) $base()->where('status', 'sent')->where('billable', true)->where('created_at', '>=', $monthStart)->sum('rate'), 2) : 0.0,
            ],
            'total'      => self::tracked() ? $base()->where('status', 'sent')->count() : 0,
            'messages'   => $rows->map(fn (EmailMessage $m) => [
                'id'      => $m->id,
                'at'      => $m->created_at,
                'name'    => ($m->student_detail_id ? ($students[$m->student_detail_id] ?? null) : null)
                             ?: ($m->user_id ? ($users[$m->user_id] ?? null) : null) ?: '—',
                'to'      => $m->to_email,
                'subject' => $m->subject,
                'status'  => $m->status,
                'error'   => $m->error,
                'charge'  => !$m->billable ? 'free' : ($m->status === 'failed' ? 'none' : (float) $m->rate),
            ])->all(),
        ];
    }
}
