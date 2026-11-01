<?php

namespace App\Support;

use App\Models\Messaging\MessagingCampaign;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;

/**
 * Who a school's message goes to (More → Messaging → Send): its students — all,
 * a class, or a section — its teachers, or everyone. Each recipient is
 * ['key', 'name', 'phone', 'email', 'user_id', 'student_detail_id'], students
 * first, in id order, so a message sent in parts goes on where it stopped.
 */
class MessagingAudience
{
    public const AUDIENCES = [
        'students' => 'Students',
        'teachers' => 'Teachers',
        'everyone' => 'Students and teachers',
    ];

    /** Everyone a message to $audience reaches. */
    public static function recipients(int $orgId, string $audience, ?int $standardId = null, ?int $sectionId = null): array
    {
        $out = [];

        if (in_array($audience, ['students', 'everyone'], true)) {
            $students = StudentDetail::with('user:id,name,email,mobile_number')
                ->where('organization_id', $orgId)
                ->when($audience === 'students' && $standardId, fn ($q) => $q->where('standard_id', $standardId))
                ->when($audience === 'students' && $sectionId, fn ($q) => $q->where('section_id', $sectionId))
                ->orderBy('id')
                ->get(['id', 'user_id', 'full_name', 'email', 'phone']);

            foreach ($students as $s) {
                $out[] = [
                    'key'               => 's' . $s->id,
                    'name'              => $s->full_name ?: ($s->user?->name ?? 'Student'),
                    'phone'             => $s->phone ?: $s->user?->mobile_number,
                    'email'             => $s->email ?: $s->user?->email,
                    'user_id'           => $s->user_id,
                    'student_detail_id' => $s->id,
                ];
            }
        }

        if (in_array($audience, ['teachers', 'everyone'], true)) {
            $teachers = TeacherDetail::with('user:id,name,email,mobile_number')
                ->where('organization_id', $orgId)
                ->orderBy('id')
                ->get(['id', 'user_id', 'phone']);

            foreach ($teachers as $t) {
                $out[] = [
                    'key'               => 't' . $t->id,
                    'name'              => $t->user?->name ?? 'Teacher',
                    'phone'             => $t->phone ?: $t->user?->mobile_number,
                    'email'             => $t->user?->email,
                    'user_id'           => $t->user_id,
                    'student_detail_id' => null,
                ];
            }
        }

        return $out;
    }

    public static function forCampaign(MessagingCampaign $campaign): array
    {
        return self::recipients((int) $campaign->organization_id, $campaign->audience, $campaign->standard_id, $campaign->section_id);
    }

    /** "Class 6 · Section A", "All students", "Teachers" … */
    public static function label(int $orgId, string $audience, ?int $standardId = null, ?int $sectionId = null): string
    {
        if ($audience !== 'students') {
            return $audience === 'teachers' ? 'All teachers' : 'All students and teachers';
        }
        if (!$standardId) {
            return 'All students';
        }

        $class   = Standard::where('organization_id', $orgId)->whereKey($standardId)->value('name') ?? 'A class';
        $section = $sectionId ? Section::whereKey($sectionId)->value('name') : null;

        return $section ? $class . ' · Section ' . $section : $class . ' · All sections';
    }
}
