<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Org-wide activity feed for admins.
 *
 * Every meaningful database write (a student added, a fee collected, a salary
 * paid, an exam edited, a ledger entry deleted…) is captured globally via
 * Eloquent's model events and turned into an in-app notification for the
 * school's admins — including activity performed by accountants, teachers and
 * student/user accounts. Each notification spells out WHAT happened, WHO did
 * it (name + role) and the record it touched.
 *
 * Design notes:
 *  - We only ever look at `App\Models\*`. That structurally excludes
 *    Illuminate\Notifications\DatabaseNotification, so writing a notification
 *    can never re-trigger this listener (no recursion).
 *  - Writes with no authenticated user (CLI, seeders, queued jobs) are
 *    skipped — there is no "who" to report, and we don't want migration noise.
 *  - High-volume / per-row / infra models are excluded so the feed stays
 *    useful instead of flooding (attendance rows, pivots, bulk marks, ID
 *    cards, chat, FCM tokens, super-admin platform tables…).
 *  - The whole handler is wrapped so a notification failure can NEVER break
 *    the real operation that triggered it.
 */
class ActivityNotifier
{
    /** Whole-namespace exclusions (prefix match on the FQCN). */
    protected static array $excludePrefixes = [
        'App\\Models\\Chat\\',          // chat has its own notifier
        'App\\Models\\SuperAdmin\\',    // platform scope, not a school admin's concern
        'App\\Models\\Admin\\Seating\\',// per-seat / per-room bulk rows
        'App\\Models\\Calendar\\',      // timetable building writes many sub-rows
        'App\\Models\\Mcq\\',           // per-question / per-answer churn
    ];

    /** Exact FQCN exclusions: bulk per-row writes, pivots and infra. */
    protected static array $excludeClasses = [
        'App\\Models\\UserFcmToken',
        'App\\Models\\Role',
        'App\\Models\\OrganizationModule',
        'App\\Models\\EmailTemplate',
        'App\\Models\\Student\\StudentAttendance',
        'App\\Models\\Student\\StudentSyllabus',
        'App\\Models\\Student\\SectionSubject',
        'App\\Models\\Student\\StandardSubject',
        'App\\Models\\Admin\\AdminAttendance',
        'App\\Models\\Admin\\ExamCopy',
        'App\\Models\\Admin\\ExamSubjectMark',
        'App\\Models\\Admin\\ExamSyllabusChapter',
        'App\\Models\\Admin\\EmployeeIdCard',
        'App\\Models\\Admin\\StudentIdCard',
        'App\\Models\\Admin\\TeacherIdCard',
        'App\\Models\\Admin\\TeacherTimeTable',
        'App\\Models\\Admin\\TeacherAvailability',
        'App\\Models\\Admin\\TransportationStudent',
        'App\\Models\\Teacher\\TeacherAttendance',
        'App\\Models\\Teacher\\TeacherSubject',
        'App\\Models\\Teacher\\TeacherSection',
        'App\\Models\\Teacher\\TeacherAssignment',
        'App\\Models\\Teacher\\AssignTeacherStandard',
    ];

    /** Friendly names for record types. Falls back to a humanised basename. */
    protected static array $labels = [
        'StudentDetail'        => 'Student',
        'TeacherDetail'        => 'Teacher',
        'AdminEmployee'        => 'Employee',
        'FeePayment'           => 'Fee Payment',
        'TransportFeePayment'  => 'Transport Fee Payment',
        'AdminSalaryPayment'   => 'Salary Payment',
        'LedgerTransaction'    => 'Ledger Entry',
        'FeeStructure'         => 'Fee Structure',
        'FeeCycle'             => 'Fee Cycle',
        'FeeSettings'          => 'Fee Settings',
        'ExamSyllabusChapter'  => 'Exam Syllabus',
        'ReportCard'           => 'Report Card',
        'AdmissionEnquiry'     => 'Admission Enquiry',
        'AdminEnquiry'         => 'Enquiry',
        'AdmissionExamPaper'   => 'Admission Exam Paper',
        'TransferCertificate'  => 'Transfer Certificate',
        'HomeWork'             => 'Homework',
        'RulesAndRegulation'   => 'Rules & Regulation',
        'TermAndCondition'     => 'Terms & Conditions',
        'SchoolInfo'           => 'School Info',
        'SchoolDocument'       => 'School Document',
        'SchoolManagementTeam' => 'Management Team',
        'SchoolUser'           => 'Account User',
        'DriverDetail'         => 'Driver',
        'AdmitCard'            => 'Admit Card',
        'TeacherArrangement'   => 'Teacher Arrangement',
    ];

    protected static array $roleLabels = [
        'admin'       => 'Admin',
        'sub-admin'   => 'Sub-admin',
        'accounts'    => 'Accountant',
        'teacher'     => 'Teacher',
        'user'        => 'Student',
        'super_admin' => 'Super Admin',
    ];

    /** Columns that don't count as a "real" change on an update. */
    protected static array $ignoredChangeKeys = [
        'updated_at', 'created_at', 'last_login_at', 'remember_token',
        'read_at', 'password', 'remember_token', 'fcm_token',
    ];

    /** Register the global listeners. Call once from a service provider. */
    public static function boot(): void
    {
        $verbs = ['created' => 'added', 'updated' => 'updated', 'deleted' => 'deleted', 'restored' => 'restored'];

        foreach ($verbs as $verb => $word) {
            Event::listen("eloquent.{$verb}: *", function ($eventName, $payload) use ($verb, $word) {
                $model = is_array($payload) ? ($payload[0] ?? null) : $payload;
                if ($model instanceof Model) {
                    static::handle($verb, $word, $model);
                }
            });
        }
    }

    protected static function handle(string $verb, string $word, Model $model): void
    {
        try {
            $class = get_class($model);

            // Domain models only → also excludes DatabaseNotification (no recursion).
            if (!Str::startsWith($class, 'App\\Models\\')) {
                return;
            }
            foreach (static::$excludePrefixes as $prefix) {
                if (Str::startsWith($class, $prefix)) {
                    return;
                }
            }
            if (in_array($class, static::$excludeClasses, true)) {
                return;
            }

            // Student/teacher User rows are announced through their detail
            // models (which carry the name); skip the bare User row to avoid
            // a duplicate. Accounts / sub-admin / admin Users are announced
            // here since they have no separate detail model.
            if ($class === 'App\\Models\\User' && in_array($model->role ?? null, ['user', 'teacher'], true)) {
                return;
            }

            $actor = Auth::user();
            if (!$actor) {
                return; // CLI / seeders / queued jobs — no actor to attribute
            }

            // Ignore updates that only touched bookkeeping columns.
            if ($verb === 'updated') {
                $meaningful = array_diff(array_keys($model->getChanges()), static::$ignoredChangeKeys);
                if (empty($meaningful)) {
                    return;
                }
            }

            $orgId = (int) ($model->organization_id ?? 0) ?: (int) ($actor->organization_id ?? 0);
            if (!$orgId) {
                return;
            }

            // Recipients: ALL of the school's admins — including the actor when
            // the actor is an admin. The requirement is that admins see EVERY
            // activity, their own included (a single-admin school must still
            // get a feed of what it does), as well as everything accountants,
            // teachers and student/user accounts do.
            $admins = User::query()
                ->whereIn('role', ['admin', 'sub-admin'])
                ->where('organization_id', $orgId)
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, new ActivityNotification(
                static::buildPayload($verb, $word, $model, $actor, $orgId)
            ));
        } catch (\Throwable $e) {
            // A notification must never break the action that triggered it.
            logger()->warning('ActivityNotifier failed: ' . $e->getMessage());
        }
    }

    /** @return array<string,mixed> */
    protected static function buildPayload(string $verb, string $word, Model $model, $actor, int $orgId): array
    {
        $actorRole = static::$roleLabels[$actor->role] ?? Str::headline((string) $actor->role);
        $actorName = $actor->name ?: 'Someone';
        $subject   = static::subjectLabel($model);
        $desc      = static::descriptor($model);

        $body = "{$actorName} ({$actorRole}) {$word} " . static::article($subject) . " {$subject}";
        if ($desc !== '') {
            $body .= " — {$desc}";
        }
        if ($verb === 'updated') {
            $changed = array_diff(array_keys($model->getChanges()), static::$ignoredChangeKeys);
            if (!empty($changed)) {
                $shown = array_map(fn ($c) => Str::headline($c), array_slice(array_values($changed), 0, 6));
                $body .= ' (changed: ' . implode(', ', $shown) . ')';
            }
        }
        $body .= '.';

        return [
            'type'            => 'activity',
            'title'           => "{$actorRole} {$word} {$subject}",
            'body'            => $body,
            'action'          => $verb,
            'subject'         => $subject,
            'subject_type'    => get_class($model),
            'subject_id'      => $model->getKey(),
            'actor_id'        => $actor->id,
            'actor_name'      => $actorName,
            'actor_role'      => $actor->role,
            'organization_id' => $orgId,
            'at'              => now()->toIso8601String(),
        ];
    }

    protected static function subjectLabel(Model $model): string
    {
        $base = class_basename($model);
        return static::$labels[$base] ?? Str::headline($base);
    }

    /** A short human descriptor of the record (name / receipt / amount…). */
    protected static function descriptor(Model $model): string
    {
        $parts = [];

        $nameKeys = [
            'full_name', 'name', 'title', 'exam_name', 'student_name',
            'teacher_name', 'receipt_number', 'label', 'reason', 'month',
            'subject', 'enquiry_for', 'father_name',
        ];
        foreach ($nameKeys as $key) {
            $val = $model->getAttribute($key);
            if (is_string($val) && trim($val) !== '') {
                $parts[] = Str::limit(trim($val), 60);
                break;
            }
        }

        $amount = $model->getAttribute('amount');
        if (is_numeric($amount) && (float) $amount > 0) {
            $parts[] = '₹' . number_format((float) $amount, 2);
        }

        return implode(' · ', $parts);
    }

    protected static function article(string $word): string
    {
        return in_array(strtoupper($word[0] ?? ''), ['A', 'E', 'I', 'O', 'U'], true) ? 'an' : 'a';
    }
}
