<?php

namespace App\Services;

use App\Models\Admin\ContactAdminTeacher;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamDatesheet;
use App\Models\Admin\ExamSyllabusChapter;
use App\Models\Admin\HomeWork;
use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Chapter;
use App\Models\Student\Subject;
use App\Models\Student\Topic;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use App\Models\User;
use App\Services\Concerns\GathersPushes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The teacher app's pushes: which school event tells a teacher what.
 *
 *  - their profile edited (each field that changed)
 *  - their attendance marked (the status, at once)
 *  - homework the school adds, edits or deletes in a class and subject they teach
 *  - their timetable changed (periods added, moved or removed, and arrangements)
 *  - a subject newly given to them
 *  - chapters and topics the school changes in their subjects
 *  - an exam of theirs added, changed or removed, its date sheet issued, and its
 *    syllabus changed (only a change — not the first time it is set)
 *  - the school's reply to their Contact School query
 *
 * Announcements, the More pages and chat stay with {@see AppPushNotifier} and
 * the chat controller.
 *
 * Pushes are gathered per teacher and sent when the request ends
 * ({@see GathersPushes}).
 */
class TeacherPushNotifier
{
    use GathersPushes;

    private const TEACHER = 'teacher';

    private const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    /** Profile columns (users + teacher_details) and how the push names them. */
    private const PROFILE_FIELDS = [
        'name'              => 'Name',
        'email'             => 'Email',
        'mobile_number'     => 'Mobile number',
        'phone'             => 'Mobile number',
        'image'             => 'Photo',
        'dob'               => 'Date of birth',
        'gender'            => 'Gender',
        'is_active'         => 'Account',
        'employee_id'       => 'Employee ID',
        'date_of_joining'   => 'Date of joining',
        'qualification'     => 'Qualification',
        'address'           => 'Address',
        'city'              => 'City',
        'state'             => 'State',
        'pincode'           => 'Pincode',
        'emergency_contact' => 'Emergency contact',
    ];

    private const EXAM_FIELDS = [
        'exam_name'     => 'Name',
        'exam_type'     => 'Type',
        'term'          => 'Term',
        'academic_year' => 'Academic year',
        'total_marks'   => 'Total marks',
        'passing_marks' => 'Passing marks',
    ];

    // ── Profile ───────────────────────────────────────────────────────────────

    /** A teacher's users row was saved. */
    public function userUpdated(User $user): void
    {
        if ($user->role !== self::TEACHER) {
            return;
        }
        $this->safe(fn () => $this->profileChanged((int) $user->id, $this->modelChanges($user)));
    }

    /** A teacher's teacher_details row was saved. */
    public function teacherDetailUpdated(TeacherDetail $detail): void
    {
        $this->safe(fn () => $this->profileChanged((int) $detail->user_id, $this->modelChanges($detail)));
    }

    /**
     * The profile as it stands, for a save that bypasses model events — pass it
     * back to {@see profileSaved()} afterwards.
     */
    public function profileSnapshot(int $userId): ?array
    {
        return $this->attempt(fn () => ['user_id' => $userId, 'values' => $this->profileValues($userId)]);
    }

    public function profileSaved(?array $before): void
    {
        if (!$before) {
            return;
        }
        $this->safe(function () use ($before) {
            $after = $this->profileValues($before['user_id']);
            $changes = [];
            foreach ($after as $field => $value) {
                $changes[$field] = [$before['values'][$field] ?? null, $value];
            }
            $this->profileChanged($before['user_id'], $changes);
        });
    }

    // ── Attendance ────────────────────────────────────────────────────────────

    /** A teacher's attendance was marked (or its status / remark changed). */
    public function attendanceSaved(TeacherAttendance $a): void
    {
        $this->safe(function () use ($a) {
            if (!$a->wasRecentlyCreated && !$a->wasChanged(['status', 'remarks'])) {
                return;
            }
            $userId = $this->userIdOfTeacher((int) $a->teacher_detail_id);
            if (!$userId || !Auth::check() || (int) Auth::id() === $userId) {
                return;
            }

            $status = match ((int) $a->status) {
                1       => 'Present',
                0       => 'Absent',
                2       => 'Half Day',
                3, 4    => 'Holiday',
                default => null,
            };
            if (!$status) {
                return;
            }

            $date  = Carbon::parse($a->attendance_date);
            $when  = $date->isToday() ? 'today' : 'on ' . $date->format('d M Y');
            $lines = trim((string) $a->remarks) !== '' ? ['Remark: ' . Str::limit(trim((string) $a->remarks), 200)] : [];

            $this->queue($userId, 'attendance:' . $date->toDateString(), [
                'type'   => 'attendance_marked',
                'title'  => $a->wasRecentlyCreated ? 'Attendance Marked' : 'Attendance Updated',
                'intro'  => "Your attendance {$when} has been marked {$status}.",
                'screen' => 'Attendance',
                'params' => ['month' => $date->format('Y-m'), 'monthAt' => (int) round(microtime(true) * 1000)],
            ], $lines);
        });
    }

    // ── Homework ──────────────────────────────────────────────────────────────

    /** The school added, edited or deleted homework — tell who teaches that class and subject. */
    public function homeworkBySchool(HomeWork $hw, string $verb): void
    {
        $this->safe(function () use ($hw, $verb) {
            $actor = $this->schoolActor();
            if (!$actor) {
                return;
            }

            $what = '';
            if ($verb === 'updated') {
                $changed = [];
                foreach (['title' => 'title', 'description' => 'description', 'file' => 'attachment',
                          'subject_id' => 'subject', 'standard_id' => 'class', 'section_id' => 'section'] as $col => $label) {
                    if ($hw->wasChanged($col) && $this->norm($hw->getRawOriginal($col)) !== $this->norm($hw->getAttributes()[$col] ?? null)) {
                        $changed[] = $label;
                    }
                }
                if (!$changed) {
                    return;
                }
                $what = ' (changed: ' . implode(', ', $changed) . ')';
            }

            $rows = $this->teachingRows((int) $hw->organization_id, (int) $hw->standard_id, $this->sectionOrNull($hw->section_id), [(int) $hw->subject_id]);
            $line = $this->subjectName((int) $hw->subject_id) . ' · ' . $this->className((int) $hw->standard_id, $this->sectionOrNull($hw->section_id))
                . ': ' . Str::limit((string) $hw->title, 80) . $what;

            [$title, $intro] = match ($verb) {
                'created' => ['Homework Added', 'The school added homework in your subject:'],
                'deleted' => ['Homework Deleted', 'The school deleted homework in your subject:'],
                default   => ['Homework Updated', 'The school updated homework in your subject:'],
            };

            foreach ($rows->pluck('user_id')->unique() as $userId) {
                if ($userId === (int) $actor->id) {
                    continue;
                }
                $this->queue($userId, 'homework:' . $verb, [
                    'type'   => 'homework_assigned',
                    'title'  => $title,
                    'intro'  => $intro,
                    'screen' => 'Homework',
                ], ['hw:' . $hw->id => $line]);
            }
        });
    }

    // ── Timetable & subjects ──────────────────────────────────────────────────

    /** A class's timetable before a save — hand it to {@see timetableSaved()} after. */
    public function timetableSnapshot(int $orgId, int $standardId, int $sectionId): ?array
    {
        return $this->attempt(fn () => [
            'org'      => $orgId,
            'standard' => $standardId,
            'section'  => $sectionId,
            'rows'     => $this->timetableRows($orgId, $standardId, $sectionId),
        ]);
    }

    /**
     * A class's timetable was saved or deleted: each teacher whose periods in it
     * changed hears which, and one given a subject they did not teach before
     * hears that too.
     */
    public function timetableSaved(?array $before): void
    {
        if (!$before) {
            return;
        }
        $this->safe(function () use ($before) {
            if (!$this->schoolActor()) {
                return;
            }

            ['org' => $orgId, 'standard' => $standardId, 'section' => $sectionId] = $before;
            $old = collect($before['rows'])->groupBy('teacher');
            $new = collect($this->timetableRows($orgId, $standardId, $sectionId))->groupBy('teacher');
            $class = $this->className($standardId, $sectionId);

            foreach ($old->keys()->merge($new->keys())->unique() as $teacherId) {
                $userId = $this->userIdOfTeacher((int) $teacherId);
                if (!$userId) {
                    continue;
                }

                $was = collect($old->get($teacherId, []));
                $now = collect($new->get($teacherId, []));
                $lines = $this->periodChanges($was, $now);
                if ($lines) {
                    $this->queue($userId, "timetable:{$standardId}:{$sectionId}", [
                        'type'   => 'timetable_changed',
                        'title'  => 'Timetable Changed',
                        'intro'  => "Your timetable for {$class} has changed:",
                        'screen' => 'Timetable',
                    ], $lines);
                }

                // Subjects new to the teacher in this class — not in its old
                // timetable and not given to them as an assigned subject.
                $gained = $now->pluck('subject')->unique()->diff($was->pluck('subject')->unique());
                if ($gained->isNotEmpty()) {
                    $assigned = TeacherSubject::where('teacher_detail_id', $teacherId)
                        ->where('standard_id', $standardId)->where('section_id', $sectionId)
                        ->pluck('subject_id')->map(fn ($id) => (int) $id);
                    $gained = $gained->diff($assigned);
                }
                if ($gained->isNotEmpty()) {
                    $this->queue($userId, 'subjects', [
                        'type'   => 'subject_assigned',
                        'title'  => 'New Subject Assigned',
                        'intro'  => 'You have been assigned:',
                        'screen' => 'Subjects',
                    ], $gained->mapWithKeys(fn ($subjectId) => [
                        "s:{$subjectId}:{$standardId}:{$sectionId}" => $this->subjectName($subjectId) . ' · ' . $class,
                    ])->all());
                }
            }
        });
    }

    /** A substitute was put on, moved off or taken off a period. */
    public function arrangementChanged(TeacherArrangement $a, string $verb): void
    {
        $this->safe(function () use ($a, $verb) {
            if (!$this->schoolActor()) {
                return;
            }
            $date = Carbon::parse($a->date);
            if ($date->lt(today())) {
                return;
            }

            $slot = TeacherTimeTable::find($a->teacher_time_table_id);
            if (!$slot) {
                return;
            }
            $when   = $date->isToday() ? 'today' : 'on ' . $date->format('D, d M');
            $period = $this->subjectName((int) $slot->subject_id) . ' · ' . $this->className((int) $slot->standard_id, (int) $slot->section_id)
                . ' · ' . $this->timeRange($slot->start_time, $slot->end_time);
            $original = (int) $a->original_teacher_id;
            $substitute = (int) $a->substitute_teacher_id;
            $group = 'arrangement:' . $date->toDateString();
            $meta = ['type' => 'timetable_changed', 'title' => 'Timetable Changed', 'screen' => 'Timetable'];
            $reason = trim((string) $a->reason) !== '' ? ['Reason: ' . Str::limit(trim((string) $a->reason), 120)] : [];

            $take = fn (int $sub) => $this->queue($this->userIdOfTeacher($sub), $group,
                $meta + ['intro' => "You have an arrangement period {$when}:"],
                array_merge(["a:{$a->id}" => $period . ' — in place of ' . $this->teacherName($original)], $reason));
            $drop = fn (int $sub) => $this->queue($this->userIdOfTeacher($sub), $group,
                $meta + ['intro' => "Your arrangement period {$when} is cancelled:"], ["a:{$a->id}" => $period]);

            if ($verb === 'created') {
                $take($substitute);
                $this->queue($this->userIdOfTeacher($original), $group,
                    $meta + ['intro' => "Your period {$when} will be taken by " . $this->teacherName($substitute) . ':'], ["a:{$a->id}" => $period]);
            } elseif ($verb === 'deleted') {
                $drop($substitute);
                $this->queue($this->userIdOfTeacher($original), $group,
                    $meta + ['intro' => "Your period {$when} is back with you:"], ["a:{$a->id}" => $period]);
            } elseif ($a->wasChanged('substitute_teacher_id')) {
                $drop((int) $a->getRawOriginal('substitute_teacher_id'));
                $take($substitute);
                $this->queue($this->userIdOfTeacher($original), $group,
                    $meta + ['intro' => "Your period {$when} will now be taken by " . $this->teacherName($substitute) . ':'], ["a:{$a->id}" => $period]);
            }
        });
    }

    // ── Chapters & topics ─────────────────────────────────────────────────────

    public function chapterChanged(Chapter $c, string $verb): void
    {
        $this->safe(function () use ($c, $verb) {
            if (!$this->staffActor()) {
                return;
            }
            $name = (string) $c->name;
            $lines = [];
            if ($verb === 'created') {
                $lines["ch:{$c->id}"] = "Chapter added: {$name}";
            } elseif ($verb === 'deleted') {
                $lines["ch:{$c->id}"] = "Chapter removed: {$name}";
            } else {
                $old = (string) $c->getRawOriginal('name');
                if ($c->wasChanged('name') && trim($old) !== trim($name)) {
                    $lines["ch:{$c->id}"] = "Chapter renamed: {$old} → {$name}";
                }
                if ($line = $this->contentLine($c, ['description', 'image_path', 'pdf_path', 'file_path'], "chapter {$name}")) {
                    $lines["ch:{$c->id}:content"] = $line;
                }
            }
            $this->outlineChanged((int) $c->organization_id, (int) $c->standard_id, $this->sectionOrNull($c->section_id), (int) $c->subject_id, $lines);
        });
    }

    public function topicChanged(Topic $t, string $verb): void
    {
        $this->safe(function () use ($t, $verb) {
            if (!$this->staffActor()) {
                return;
            }
            $chapter = Chapter::find($t->chapter_id);
            if (!$chapter) {
                return;
            }
            $name = (string) $t->topic_name;
            $in = (string) $chapter->name;
            $lines = [];
            if ($verb === 'created') {
                $lines["tp:{$chapter->id}:{$t->id}"] = "Topic added in {$in}: {$name}";
            } elseif ($verb === 'deleted') {
                $lines["tp:{$chapter->id}:{$t->id}"] = "Topic removed from {$in}: {$name}";
            } else {
                $old = (string) $t->getRawOriginal('topic_name');
                if ($t->wasChanged('topic_name') && trim($old) !== trim($name)) {
                    $lines["tp:{$chapter->id}:{$t->id}"] = "Topic renamed in {$in}: {$old} → {$name}";
                }
                if ($line = $this->contentLine($t, ['topic_content', 'image_path', 'pdf_path', 'link'], "topic {$name} ({$in})")) {
                    $lines["tp:{$chapter->id}:{$t->id}:content"] = $line;
                }
            }
            $this->outlineChanged((int) $chapter->organization_id, (int) $chapter->standard_id, $this->sectionOrNull($chapter->section_id), (int) $chapter->subject_id, $lines);
        });
    }

    /**
     * A subject's chapters and topics (in every class) before a save that goes
     * around model events — the panel's Syllabus lists. Hand it to
     * {@see outlineSaved()} afterwards.
     */
    public function outlineSnapshot(int $orgId, int $subjectId): ?array
    {
        return $this->attempt(fn () => ['org' => $orgId, 'subject' => $subjectId, 'chapters' => $this->outline($orgId, $subjectId)]);
    }

    /** The snapshot of the subject a chapter belongs to. */
    public function outlineSnapshotOfChapter(int $chapterId): ?array
    {
        $c = $this->attempt(fn () => Chapter::find($chapterId));

        return $c ? $this->outlineSnapshot((int) $c->organization_id, (int) $c->subject_id) : null;
    }

    public function outlineSaved(?array $before): void
    {
        if (!$before) {
            return;
        }
        $this->safe(function () use ($before) {
            if (!$this->staffActor()) {
                return;
            }
            $was = $before['chapters'];
            $now = $this->outline($before['org'], $before['subject']);
            // Lines per class the chapter sits in: "standard|section" → lines.
            $byClass = [];
            $add = function (array $c, string $key, string $line) use (&$byClass) {
                $byClass["{$c['standard']}|{$c['section']}"][$key] = $line;
            };

            foreach ($was as $id => $c) {
                if (!isset($now[$id])) {
                    $add($c, "ch:{$id}", "Chapter removed: {$c['name']}");
                    continue;
                }
                $n = $now[$id];
                if (trim($c['name']) !== trim($n['name'])) {
                    $add($n, "ch:{$id}", "Chapter renamed: {$c['name']} → {$n['name']}");
                }
                foreach ($c['topics'] as $tid => $topic) {
                    if (!isset($n['topics'][$tid])) {
                        $add($n, "tp:{$id}:{$tid}", "Topic removed from {$n['name']}: {$topic}");
                    } elseif (trim($topic) !== trim($n['topics'][$tid])) {
                        $add($n, "tp:{$id}:{$tid}", "Topic renamed in {$n['name']}: {$topic} → {$n['topics'][$tid]}");
                    }
                }
                foreach (array_diff_key($n['topics'], $c['topics']) as $tid => $topic) {
                    $add($n, "tp:{$id}:{$tid}", "Topic added in {$n['name']}: {$topic}");
                }
            }
            foreach (array_diff_key($now, $was) as $id => $c) {
                $add($c, "ch:{$id}", "Chapter added: {$c['name']}");
            }

            foreach ($byClass as $class => $lines) {
                [$standardId, $sectionId] = array_map('intval', explode('|', $class));
                $this->outlineChanged($before['org'], $standardId, $sectionId ?: null, $before['subject'], $lines);
            }
        });
    }

    // ── Exams ─────────────────────────────────────────────────────────────────

    /** An exam was added or edited. Teachers only see a published exam, so only that is told. */
    public function examSaved(Exam $exam, string $verb): void
    {
        $this->safe(function () use ($exam, $verb) {
            if (!$this->schoolActor()) {
                return;
            }
            $name = (string) $exam->exam_name;
            $was = $verb === 'created' ? false : (bool) $exam->getRawOriginal('is_published');
            $is = (bool) $exam->is_published;
            $detail = ['type' => 'exam_updated', 'screen' => 'ExamDetail', 'params' => ['examId' => $exam->id, 'teacher' => true]];

            if (!$was && !$is) {
                return;
            }
            if (!$was) {
                $push = $detail + ['title' => 'New Exam', 'intro' => "{$name} has been scheduled."];
                $lines = ['dates' => 'Dates: ' . $this->dateRange($exam->start_date, $exam->end_date)];
                if ($exam->exam_type) {
                    $lines['type'] = 'Type: ' . Str::headline((string) $exam->exam_type);
                }
            } elseif (!$is) {
                $push = ['type' => 'exam_updated', 'title' => 'Exam Withdrawn', 'intro' => "{$name} has been withdrawn.",
                         'screen' => 'ExamsScreen', 'params' => ['teacher' => true]];
                $lines = [];
            } else {
                $lines = [];
                foreach (self::EXAM_FIELDS as $col => $label) {
                    $old = $exam->getRawOriginal($col);
                    $new = $exam->getAttributes()[$col] ?? null;
                    if ($exam->wasChanged($col) && $this->norm($old) !== $this->norm($new)) {
                        $lines[$col] = "{$label}: " . $this->examValue($col, $old) . ' → ' . $this->examValue($col, $new);
                    }
                }
                $datesChanged = false;
                foreach (['start_date', 'end_date'] as $col) {
                    if ($exam->wasChanged($col) && $this->day($exam->getRawOriginal($col)) !== $this->day($exam->getAttributes()[$col] ?? null)) {
                        $datesChanged = true;
                    }
                }
                if ($datesChanged) {
                    $lines['dates'] = 'Dates: ' . $this->dateRange($exam->getRawOriginal('start_date'), $exam->getRawOriginal('end_date'))
                        . ' → ' . $this->dateRange($exam->start_date, $exam->end_date);
                }
                if ($exam->wasChanged('description') && $this->norm($exam->getRawOriginal('description')) !== $this->norm($exam->description)) {
                    $lines['description'] = 'Description updated';
                }
                if (!$lines) {
                    return;
                }
                $push = $detail + ['title' => 'Exam Updated', 'intro' => "{$name} has been updated:"];
            }

            foreach ($this->examTeacherUserIds($exam) as $userId) {
                $this->queue($userId, 'exam:' . $exam->id, $push, $lines);
            }
        });
    }

    /** An exam is about to be deleted — call before its syllabus rows go, which decide who teaches it. */
    public function examDeleting(Exam $exam): void
    {
        $this->safe(function () use ($exam) {
            if (!$this->schoolActor() || !$exam->is_published) {
                return;
            }
            foreach ($this->examTeacherUserIds($exam) as $userId) {
                $this->queue($userId, 'exam:' . $exam->id, [
                    'type'   => 'exam_updated',
                    'title'  => 'Exam Removed',
                    'intro'  => "{$exam->exam_name} has been removed.",
                    'screen' => 'ExamsScreen',
                    'params' => ['teacher' => true],
                ], []);
            }
        });
    }

    /** A class's date sheet for an exam before it is saved — hand it to {@see datesheetSaved()}. */
    public function datesheetSnapshot(int $orgId, ?int $datesheetId, int $examId, int $standardId, ?int $sectionId): ?array
    {
        return $this->attempt(function () use ($orgId, $datesheetId, $examId, $standardId, $sectionId) {
            $sheet = $datesheetId
                ? ExamDatesheet::with('papers')->where('organization_id', $orgId)->find($datesheetId)
                : $this->datesheetOf($orgId, $examId, $standardId, $sectionId);

            return ['org' => $orgId, 'papers' => $sheet ? $this->paperKeys($sheet) : []];
        });
    }

    /** A date sheet was saved: each teacher of its subjects hears their own papers, if they changed. */
    public function datesheetSaved(?array $before, int $examId, int $standardId, ?int $sectionId): void
    {
        if (!$before) {
            return;
        }
        $this->safe(function () use ($before, $examId, $standardId, $sectionId) {
            if (!$this->schoolActor()) {
                return;
            }
            $orgId = $before['org'];
            $sheet = $this->datesheetOf($orgId, $examId, $standardId, $sectionId);
            $exam = Exam::find($examId);
            if (!$sheet || !$exam || !$exam->is_published) {
                return;
            }
            $sheet->loadMissing('papers.subject');
            $now = $this->paperKeys($sheet);
            $subjects = $sheet->papers->pluck('subject_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
            $class = $this->className($standardId, $sectionId);
            $isNew = empty($before['papers']);

            // The class's students hear of the whole sheet, when any paper changed.
            if ($before['papers'] != $now) {
                app(StudentPushNotifier::class)->datesheetIssued($exam, $sheet, $standardId, $sectionId, $isNew);
            }

            foreach ($this->teachingRows($orgId, $standardId, $sectionId, $subjects)->groupBy('user_id') as $userId => $rows) {
                $mine = $rows->pluck('subject_id')->unique();
                $changed = $mine->contains(fn ($s) => ($before['papers'][$s] ?? null) !== ($now[$s] ?? null));
                if (!$changed) {
                    continue;
                }
                $lines = $sheet->papers->whereIn('subject_id', $mine->all())->sortBy('exam_date')
                    ->mapWithKeys(fn ($p) => ['p:' . $p->subject_id => ($p->subject->name ?? 'Subject') . ' — '
                        . Carbon::parse($p->exam_date)->format('D, d M')
                        . ($p->start_time ? ' · ' . $this->timeRange($p->start_time, $p->end_time) : '')])
                    ->all();

                $this->queue((int) $userId, "datesheet:{$examId}:{$standardId}:" . ($sectionId ?? 0), [
                    'type'   => 'datesheet_issued',
                    'title'  => $isNew ? 'Date Sheet Issued' : 'Date Sheet Updated',
                    'intro'  => "{$exam->exam_name} date sheet for {$class}:",
                    'screen' => 'DateSheet',
                    'params' => ['teacher' => true],
                ], $lines);
            }
        });
    }

    /**
     * An exam's syllabus buckets (exam · class · subject) that a save can touch:
     * the one being saved and any that hold a chapter it takes over.
     */
    public function examSyllabusSnapshot(int $orgId, int $examId, int $standardId, int $subjectId, array $chapterIds = []): ?array
    {
        return $this->attempt(function () use ($orgId, $examId, $standardId, $subjectId, $chapterIds) {
            $keys = ["{$examId}|{$standardId}|{$subjectId}"];
            if ($chapterIds) {
                ExamSyllabusChapter::where('organization_id', $orgId)->whereIn('chapter_id', $chapterIds)
                    ->get(['exam_id', 'standard_id', 'subject_id'])
                    ->each(function ($r) use (&$keys) {
                        $keys[] = "{$r->exam_id}|{$r->standard_id}|{$r->subject_id}";
                    });
            }

            $buckets = [];
            foreach (array_unique($keys) as $key) {
                $buckets[$key] = $this->syllabusBucket($orgId, $key);
            }

            return ['org' => $orgId, 'buckets' => $buckets];
        });
    }

    /** Only a syllabus that already had chapters counts as changed — setting one the first time does not. */
    public function examSyllabusSaved(?array $before): void
    {
        if (!$before) {
            return;
        }
        $this->safe(function () use ($before) {
            if (!$this->schoolActor()) {
                return;
            }
            $orgId = $before['org'];

            foreach ($before['buckets'] as $key => $was) {
                if (empty($was['chapters'])) {
                    continue;
                }
                $now = $this->syllabusBucket($orgId, $key);
                $added = array_diff($now['chapters'], $was['chapters']);
                $removed = array_diff($was['chapters'], $now['chapters']);
                if (!$added && !$removed) {
                    continue;
                }

                [$examId, $standardId, $subjectId] = array_map('intval', explode('|', $key));
                $exam = Exam::find($examId);
                if (!$exam || !$exam->is_published) {
                    continue;
                }
                $names = Chapter::whereIn('id', array_merge($added, $removed))->pluck('name', 'id');
                $lines = [];
                if ($added) {
                    $lines['added'] = 'Added: ' . Str::limit(collect($added)->map(fn ($id) => $names[$id] ?? 'a chapter')->implode(', '), 300);
                }
                if ($removed) {
                    $lines['removed'] = 'Removed: ' . Str::limit(collect($removed)->map(fn ($id) => $names[$id] ?? 'a chapter')->implode(', '), 300);
                }
                if (!$now['chapters']) {
                    $lines['removed'] = 'The syllabus was removed.';
                }

                $section = $was['section'];
                app(StudentPushNotifier::class)->examSyllabusChanged($exam, $standardId, $section, $subjectId, $lines);
                $intro = "{$exam->exam_name} syllabus for " . $this->subjectName($subjectId) . ' · ' . $this->className($standardId, $section) . ' has changed:';
                foreach ($this->teachingRows($orgId, $standardId, $section, [$subjectId])->pluck('user_id')->unique() as $userId) {
                    $this->queue((int) $userId, "syllabus:{$key}", [
                        'type'   => 'exam_syllabus_updated',
                        'title'  => 'Exam Syllabus Updated',
                        'intro'  => $intro,
                        'screen' => 'ExamSyllabus',
                        'params' => ['teacher' => true],
                    ], $lines);
                }
            }
        });
    }

    // ── Contact School ────────────────────────────────────────────────────────

    /** The school replied to (or changed its reply on) a teacher's query. */
    public function contactReplied(ContactAdminTeacher $c): void
    {
        $this->safe(function () use ($c) {
            $reply = trim((string) $c->admin_text);
            if (!$c->wasChanged('admin_text') || $reply === '' || !$c->admin_reply) {
                return;
            }
            $userId = (int) $c->user_id;
            if (!$userId || (int) Auth::id() === $userId) {
                return;
            }

            $this->queue($userId, 'query:' . $c->id, [
                'type'   => 'query_replied',
                'title'  => 'Reply from School',
                'intro'  => 'Re: ' . Str::limit((string) $c->topic, 80),
                'screen' => 'ViewQuery',
                // View Query draws this until it has loaded the query itself.
                'params' => ['item' => [
                    'id'          => $c->id,
                    'subject'     => Str::limit((string) $c->topic, 120),
                    'message'     => Str::limit((string) $c->teacher_query, 300),
                    'status'      => 'Resolved',
                    'created_at'  => optional($c->created_at)->toIso8601String(),
                    'daysAgo'     => $c->created_at ? (int) $c->created_at->copy()->startOfDay()->diffInDays(today()) : 0,
                    'admin_reply' => Str::limit($reply, 800),
                    'replied_at'  => now()->toIso8601String(),
                ]],
            ], ['reply' => Str::limit($reply, 600)]);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function profileChanged(int $userId, array $changes): void
    {
        if (!$userId || !Auth::check() || (int) Auth::id() === $userId) {
            return;
        }
        $lines = [];
        foreach ($changes as $field => [$old, $new]) {
            if (!isset(self::PROFILE_FIELDS[$field]) || $this->profileValue($field, $old) === $this->profileValue($field, $new)) {
                continue;
            }
            $label = self::PROFILE_FIELDS[$field];
            $newText = $this->profileValue($field, $new);
            $oldText = $this->profileValue($field, $old);
            $lines[$label] = match (true) {
                $field === 'image'  => $newText === '' ? 'Photo removed' : 'Photo changed',
                $newText === ''     => "{$label}: removed",
                $oldText === ''     => "{$label}: {$newText}",
                default             => "{$label}: {$oldText} → {$newText}",
            };
        }
        if (!$lines) {
            return;
        }
        $this->queue($userId, 'profile', [
            'type'   => 'profile_updated',
            'title'  => 'Profile Updated',
            'intro'  => 'The school updated your profile:',
            'screen' => 'TeacherProfile',
        ], $lines);
    }

    /** [field => [old, new]] for the columns a saved model actually changed. */
    private function modelChanges(Model $model): array
    {
        $out = [];
        foreach (array_keys($model->getChanges()) as $field) {
            $out[$field] = [$model->getRawOriginal($field), $model->getAttributes()[$field] ?? null];
        }

        return $out;
    }

    private function profileValues(int $userId): array
    {
        $user = DB::table('users')->where('id', $userId)->first();
        $detail = DB::table('teacher_details')->where('user_id', $userId)->first();
        $values = [];
        foreach (array_keys(self::PROFILE_FIELDS) as $field) {
            if ($user && property_exists($user, $field)) {
                $values[$field] = $user->{$field};
            } elseif ($detail && property_exists($detail, $field)) {
                $values[$field] = $detail->{$field};
            }
        }

        return $values;
    }

    private function profileValue(string $field, $value): string
    {
        $value = $this->norm($value);
        if ($value === '') {
            return '';
        }

        return match ($field) {
            'dob', 'date_of_joining' => $this->day($value) ? Carbon::parse($value)->format('d M Y') : $value,
            'gender'                 => ucfirst($value),
            'is_active'              => $value === '1' ? 'Active' : 'Inactive',
            'image'                  => $value,
            default                  => Str::limit($value, 60),
        };
    }

    /** "Content updated in …" / "Content removed from …" when a content column changed. */
    private function contentLine(Model $m, array $columns, string $what): ?string
    {
        $changed = collect($columns)->contains(fn ($c) => $m->wasChanged($c)
            && $this->norm($m->getRawOriginal($c)) !== $this->norm($m->getAttributes()[$c] ?? null));
        if (!$changed) {
            return null;
        }
        $empty = collect($columns)->every(fn ($c) => $this->norm($m->getAttributes()[$c] ?? null) === '');

        return $empty ? "Content removed from {$what}" : "Content updated in {$what}";
    }

    /**
     * Chapter and topic lines: to the subject's teachers when the school made
     * the change, and to the class's students (their syllabus) whoever did.
     */
    private function outlineChanged(int $orgId, int $standardId, ?int $sectionId, int $subjectId, array $lines): void
    {
        if ($this->schoolActor()) {
            $this->queueOutline($orgId, $standardId, $sectionId, $subjectId, $lines);
        }
        app(StudentPushNotifier::class)->syllabusChanged($orgId, $standardId, $sectionId, $subjectId, $lines);
    }

    /** Queue outline lines to every teacher of that subject in that class, opening the subject's chapters. */
    private function queueOutline(int $orgId, int $standardId, ?int $sectionId, int $subjectId, array $lines): void
    {
        if (!$lines || !$subjectId || !$standardId) {
            return;
        }
        $subject = Subject::find($subjectId);
        $actorId = (int) Auth::id();

        foreach ($this->teachingRows($orgId, $standardId, $sectionId, [$subjectId])->groupBy('user_id') as $userId => $rows) {
            if ((int) $userId === $actorId) {
                continue;
            }
            $section = $sectionId ?? $rows->first()['section_id'];
            $class = $this->className($standardId, $section);
            $this->queue((int) $userId, "outline:{$subjectId}:{$standardId}:" . ($sectionId ?? 0), [
                'type'   => 'chapter_updated',
                'title'  => 'Chapters Updated',
                'intro'  => 'The school changed the chapters of ' . ($subject->name ?? 'your subject') . " · {$class}:",
                'screen' => 'SubjectDetails',
                'params' => ['combo' => [
                    'key'          => "{$subjectId}-{$standardId}-{$section}",
                    'subjectId'    => $subjectId,
                    'subjectName'  => $subject->name ?? 'Subject',
                    'subjectCode'  => $subject->code ?? null,
                    'subjectImage' => $subject?->iconUrl(),
                    'standardId'   => $standardId,
                    'standardName' => $this->standardName($standardId),
                    'sectionId'    => (int) $section,
                    'sectionName'  => $this->sectionName((int) $section),
                    'label'        => ($subject->name ?? 'Subject') . ' · ' . $class,
                ]],
            ], $lines);
        }
    }

    /** @return array<int, array{name:string, standard:int, section:int, topics:array<int,string>}> */
    private function outline(int $orgId, int $subjectId): array
    {
        return Chapter::with(['topics:id,chapter_id,topic_name'])
            ->where('organization_id', $orgId)->where('subject_id', $subjectId)
            ->get(['id', 'name', 'standard_id', 'section_id'])
            ->mapWithKeys(fn ($c) => [$c->id => [
                'name'     => (string) $c->name,
                'standard' => (int) $c->standard_id,
                'section'  => (int) $c->section_id,
                'topics'   => $c->topics->mapWithKeys(fn ($t) => [$t->id => (string) $t->topic_name])->all(),
            ]])
            ->all();
    }

    private function timetableRows(int $orgId, int $standardId, int $sectionId): array
    {
        return TeacherTimeTable::where('organization_id', $orgId)
            ->where('standard_id', $standardId)->where('section_id', $sectionId)
            ->get(['teacher_detail_id', 'subject_id', 'day_of_week', 'start_time', 'end_time'])
            ->map(fn ($r) => [
                'teacher' => (int) $r->teacher_detail_id,
                'subject' => (int) $r->subject_id,
                'day'     => (int) $r->day_of_week,
                'start'   => substr((string) $r->start_time, 0, 5),
                'end'     => substr((string) $r->end_time, 0, 5),
            ])->all();
    }

    /** "Maths · Mon 9:00 AM – 9:45 AM added", or "… → …" when a subject's period on a day moved. */
    private function periodChanges(Collection $was, Collection $now): array
    {
        $key = fn ($r) => "{$r['subject']}|{$r['day']}|{$r['start']}|{$r['end']}";
        $wasKeys = $was->keyBy($key);
        $nowKeys = $now->keyBy($key);
        $removed = $wasKeys->diffKeys($nowKeys)->values();
        $added = $nowKeys->diffKeys($wasKeys)->values();

        $lines = [];
        $label = fn ($r) => $this->subjectName($r['subject']) . ' · ' . (self::DAYS[$r['day']] ?? $r['day']);
        $time = fn ($r) => $this->timeRange($r['start'], $r['end']);
        $sort = fn ($r) => sprintf('%d|%s', $r['day'], $r['start']);

        foreach ($added->sortBy($sort) as $r) {
            $moved = $removed->search(fn ($o) => $o['subject'] === $r['subject'] && $o['day'] === $r['day']);
            if ($moved !== false) {
                $o = $removed->pull($moved);
                $lines["{$r['day']}|{$r['start']}"] = $label($r) . ': ' . $time($o) . ' → ' . $time($r);
            } else {
                $lines["{$r['day']}|{$r['start']}"] = $label($r) . ' ' . $time($r) . ' added';
            }
        }
        foreach ($removed->sortBy($sort) as $r) {
            $lines["{$r['day']}|{$r['start']}|x"] = $label($r) . ' ' . $time($r) . ' removed';
        }
        ksort($lines, SORT_NATURAL);

        return array_values($lines);
    }

    /**
     * Who teaches these subjects in a class — by the timetable or as an
     * assigned subject — one row per teacher and subject. No section means every
     * section of the class.
     *
     * @return Collection<int, array{user_id:int, section_id:int, subject_id:int}>
     */
    private function teachingRows(int $orgId, int $standardId, ?int $sectionId, array $subjectIds): Collection
    {
        if (!$orgId || !$standardId || !$subjectIds) {
            return collect();
        }
        $pick = fn ($q) => $q->where('organization_id', $orgId)->where('standard_id', $standardId)
            ->whereIn('subject_id', $subjectIds)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->get(['teacher_detail_id', 'section_id', 'subject_id']);

        $rows = $pick(TeacherTimeTable::query())->concat($pick(TeacherSubject::query()));
        if ($rows->isEmpty()) {
            return collect();
        }
        $users = TeacherDetail::whereIn('id', $rows->pluck('teacher_detail_id')->unique())->pluck('user_id', 'id');
        $teachers = User::whereIn('id', $users->values())->where('role', self::TEACHER)->pluck('id')->flip();

        return $rows->map(fn ($r) => [
                'user_id'    => (int) ($users[$r->teacher_detail_id] ?? 0),
                'section_id' => (int) $r->section_id,
                'subject_id' => (int) $r->subject_id,
            ])
            ->filter(fn ($r) => isset($teachers[$r['user_id']]))
            ->unique(fn ($r) => "{$r['user_id']}|{$r['section_id']}|{$r['subject_id']}")
            ->values();
    }

    /**
     * The teachers who see an exam in the app: those teaching a class and
     * subject in its syllabus, or every teacher when it has no syllabus.
     *
     * @return array<int>
     */
    private function examTeacherUserIds(Exam $exam): array
    {
        $orgId = (int) $exam->organization_id;
        $pairs = ExamSyllabusChapter::where('organization_id', $orgId)->where('exam_id', $exam->id)
            ->get(['standard_id', 'subject_id'])
            ->map(fn ($r) => (int) $r->standard_id . '-' . (int) $r->subject_id)
            ->unique();

        if ($pairs->isEmpty()) {
            return User::where('organization_id', $orgId)->where('role', self::TEACHER)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $ids = collect();
        foreach ($pairs as $pair) {
            [$standardId, $subjectId] = array_map('intval', explode('-', $pair));
            $ids = $ids->merge($this->teachingRows($orgId, $standardId, null, [$subjectId])->pluck('user_id'));
        }

        return $ids->unique()->values()->all();
    }

    private function datesheetOf(int $orgId, int $examId, int $standardId, ?int $sectionId): ?ExamDatesheet
    {
        return ExamDatesheet::with('papers')->where('organization_id', $orgId)
            ->where('exam_id', $examId)->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId), fn ($q) => $q->whereNull('section_id'))
            ->first();
    }

    /** @return array<int, string> subject → "date|start|end" */
    private function paperKeys(ExamDatesheet $sheet): array
    {
        return $sheet->papers->mapWithKeys(fn ($p) => [(int) $p->subject_id => $this->day($p->exam_date) . '|'
            . substr((string) $p->start_time, 0, 5) . '|' . substr((string) $p->end_time, 0, 5)])->all();
    }

    /** @return array{section: ?int, chapters: array<int>} */
    private function syllabusBucket(int $orgId, string $key): array
    {
        [$examId, $standardId, $subjectId] = array_map('intval', explode('|', $key));
        $rows = ExamSyllabusChapter::where('organization_id', $orgId)->where('exam_id', $examId)
            ->where('standard_id', $standardId)->where('subject_id', $subjectId)
            ->get(['chapter_id', 'section_id']);
        $sections = $rows->pluck('section_id')->unique();

        return [
            'section'  => $sections->count() === 1 && $sections->first() ? (int) $sections->first() : null,
            'chapters' => $rows->pluck('chapter_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all(),
        ];
    }

    /** Whoever is saving, when it is the school or a teacher — not a student. */
    private function staffActor(): ?User
    {
        $user = Auth::user();

        return $user && $user->role !== 'user' ? $user : null;
    }

    /** A school-side user (admin, sub-admin, accounts…) doing the save, or null. */
    private function schoolActor(): ?User
    {
        $user = Auth::user();

        return $user && !in_array($user->role, [self::TEACHER, 'user'], true) ? $user : null;
    }

    private function userIdOfTeacher(int $teacherDetailId): ?int
    {
        static $cache = [];
        if (!$teacherDetailId) {
            return null;
        }

        return $cache[$teacherDetailId] ??= (int) TeacherDetail::whereKey($teacherDetailId)->value('user_id') ?: null;
    }

    private function teacherName(int $teacherDetailId): string
    {
        $userId = $this->userIdOfTeacher($teacherDetailId);

        return ($userId ? User::whereKey($userId)->value('name') : null) ?: 'another teacher';
    }

    private function examValue(string $col, $value): string
    {
        $value = $this->norm($value);
        if ($value === '') {
            return '—';
        }

        return $col === 'exam_type' ? Str::headline($value) : Str::limit($value, 60);
    }
}
