<?php

namespace App\Services;

use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ExamDatesheet;
use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeSettings;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\ReportCard;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\AdmitCard;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\User;
use App\Services\Concerns\GathersPushes;
use App\Support\SeatLabel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The student app's pushes beyond announcements, homework, attendance, chat and
 * the More pages ({@see AppPushNotifier}):
 *
 *  - their profile edited (each field that changed)
 *  - fee reminders before an installment's due date — 10, 7 and 3 days, a day,
 *    and on the day — and the day after, when it is late and the late fee runs
 *  - a fee or transport fee paid, wherever it was booked
 *  - their bus's pickup or drop time changed
 *  - their syllabus changed (chapters and topics of a subject of their class)
 *  - an exam syllabus changed, a date sheet issued, an admit card issued, a
 *    seating plan published, a report card issued, marks uploaded
 *  - the school's reply to their Contact School query
 *
 * Pushes are gathered per student and sent when the request ends
 * ({@see GathersPushes}); a student is never told of their own change.
 */
class StudentPushNotifier
{
    use GathersPushes;

    private const STUDENT = 'user';

    /** Profile columns (users + student_details) and how the push names them. */
    private const PROFILE_FIELDS = [
        'name'                    => 'Name',
        'full_name'               => 'Name',
        'email'                   => 'Email',
        'mobile_number'           => 'Mobile number',
        'phone'                   => 'Mobile number',
        'image'                   => 'Photo',
        'is_active'               => 'Account',
        'father_name'             => "Father's name",
        'mother_name'             => "Mother's name",
        'dob'                     => 'Date of birth',
        'gender'                  => 'Gender',
        'religion'                => 'Religion',
        'local_address'           => 'Address',
        'permanent_address'       => 'Permanent address',
        'city'                    => 'City',
        'state'                   => 'State',
        'pincode'                 => 'Pincode',
        'admission_no'            => 'Admission no.',
        'date_of_admission'       => 'Date of admission',
        'roll_no'                 => 'Roll no.',
        'board'                   => 'Board',
        'aadhar_no'               => 'Aadhaar number',
        'transportation_required' => 'Transport',
        'appar_id'                => 'APAAR ID',
        'registration_number'     => 'Registration no.',
        'standard_id'             => 'Class',
        'section_id'              => 'Section',
    ];

    /** Days before an installment's due date a reminder goes out (-1: the day after, when it is late). */
    private const REMIND_AT = [10, 7, 3, 1, 0, -1];

    // ── Profile ───────────────────────────────────────────────────────────────

    public function userUpdated(User $user): void
    {
        if ($user->role !== self::STUDENT) {
            return;
        }
        $this->safe(fn () => $this->profileChanged((int) $user->id, $this->modelChanges($user)));
    }

    public function studentDetailUpdated(StudentDetail $detail): void
    {
        $this->safe(fn () => $this->profileChanged((int) $detail->user_id, $this->modelChanges($detail)));
    }

    /** The profile as it stands, for a save that bypasses model events — pass it to {@see profileSaved()} after. */
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
            $changes = [];
            foreach ($this->profileValues($before['user_id']) as $field => $value) {
                $changes[$field] = [$before['values'][$field] ?? null, $value];
            }
            $this->profileChanged($before['user_id'], $changes);
        });
    }

    // ── Fees & transport ──────────────────────────────────────────────────────

    /** A fee was booked — at the counter, on the panel or paid online. (A school-QR payment has its own push.) */
    public function feePaid(FeePayment $p): void
    {
        $this->safe(function () use ($p) {
            if ($this->fromSchoolQr($p->remark)) {
                return;
            }
            $what = match ($p->fee_type) {
                'penalty'   => 'late fee',
                'transport' => 'transport fee',
                default     => 'academic fee',
            };
            $lines = $this->receiptLines($p->receipt_number, $p->payment_mode, $p->payment_date);
            if ((float) $p->penalty_amount > 0) {
                $lines['penalty'] = 'Late fee included: ' . $this->rupees($p->penalty_amount);
            }
            if ((float) $p->waiver_amount > 0) {
                $lines['waiver'] = 'Waived: ' . $this->rupees($p->waiver_amount);
            }

            $this->queue($this->userIdOfStudent((int) $p->student_detail_id), 'fee-paid:' . $p->id, [
                'type'   => 'fee_paid',
                'title'  => 'Fee Paid',
                'intro'  => $this->rupees($p->amount) . " received for your {$what}.",
                'screen' => 'Fees',
            ], $lines);
        });
    }

    public function transportFeePaid(TransportFeePayment $p): void
    {
        $this->safe(function () use ($p) {
            if ($this->fromSchoolQr($p->remark)) {
                return;
            }
            $route = Transportation::whereKey($p->transportation_id)->value('route_name');
            $this->queue($this->userIdOfStudent((int) $p->student_detail_id), 'transport-paid:' . $p->id, [
                'type'   => 'fee_paid',
                'title'  => 'Transport Fee Paid',
                'intro'  => $this->rupees($p->amount) . ' received for your transport fee' . ($route ? " ({$route})" : '') . '.',
                'screen' => 'Fees',
            ], $this->receiptLines($p->receipt_number, $p->payment_mode, $p->payment_date));
        });
    }

    /** A route's pickup or drop time changed — tell the students who ride it. */
    public function routeTimesChanged(Transportation $t): void
    {
        $this->safe(function () use ($t) {
            $lines = [];
            foreach (['pickup_time' => 'Pickup', 'drop_time' => 'Drop'] as $col => $label) {
                $old = $this->norm($t->getRawOriginal($col));
                $new = $this->norm($t->getAttributes()[$col] ?? null);
                if ($t->wasChanged($col) && $old !== $new) {
                    $lines[$col] = "{$label}: " . ($old === '' ? '—' : $this->clock($old)) . ' → ' . ($new === '' ? '—' : $this->clock($new));
                }
            }
            if (!$lines) {
                return;
            }
            $detailIds = DB::table('transportation_students')->where('transportation_id', $t->id)->pluck('student_detail_id');
            $intro = 'Your bus timings' . ($t->route_name ? " on {$t->route_name}" : '') . ' have changed:';
            foreach ($this->studentUserIds(StudentDetail::whereIn('id', $detailIds)) as $userId) {
                $this->queue($userId, 'route-times:' . $t->id, [
                    'type'   => 'transport_updated',
                    'title'  => 'Bus Timing Changed',
                    'intro'  => $intro,
                    'screen' => 'TransportRoute',
                ], $lines);
            }
        });
    }

    /**
     * The day's fee reminders: every student with something left on an
     * academic installment falling due in 10, 7, 3 or 1 day or today, or that
     * fell due yesterday (then the late fee has started). Installments and what
     * is left on them are worked out as the app's Fees screen shows them. Run
     * once a day (fees:remind). Returns how many reminders went out.
     */
    public function sendFeeReminders(?Carbon $today = null): int
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $stageOf = [];
        foreach (self::REMIND_AT as $days) {
            $stageOf[$today->copy()->addDays($days)->toDateString()] = $days;
        }

        $due = FeeCycle::where('is_active', true)->where('fee_type', 'academic')
            ->whereDate('due_date', '>=', min(array_keys($stageOf)))
            ->whereDate('due_date', '<=', max(array_keys($stageOf)))
            ->get()
            ->filter(fn ($c) => isset($stageOf[$c->due_date->toDateString()]));

        $sent = 0;
        foreach ($due->groupBy('organization_id') as $orgId => $cycles) {
            $sent += $this->remindOrg((int) $orgId, $cycles->keyBy('id'), $stageOf, $today);
        }
        $this->flush();

        return $sent;
    }

    // ── Syllabus & exams ──────────────────────────────────────────────────────

    /**
     * Chapters or topics of a subject changed (by the school or a teacher) —
     * the class's students hear what, on their syllabus. Content (notes, files)
     * is Study Content, not the syllabus, so its lines are left out.
     */
    public function syllabusChanged(int $orgId, int $standardId, ?int $sectionId, int $subjectId, array $lines): void
    {
        $this->safe(function () use ($orgId, $standardId, $sectionId, $subjectId, $lines) {
            $lines = array_filter($lines, fn ($k) => !str_ends_with((string) $k, ':content'), ARRAY_FILTER_USE_KEY);
            if (!$lines || !$standardId || !$subjectId) {
                return;
            }
            $subject = Subject::find($subjectId);
            $name = $subject->name ?? 'Subject';
            $actorId = (int) Auth::id();

            foreach ($this->classUserIds($orgId, $standardId, $sectionId) as $userId) {
                if ($userId === $actorId) {
                    continue;
                }
                $this->queue($userId, "syllabus:{$subjectId}:{$standardId}:" . ($sectionId ?? 0), [
                    'type'   => 'syllabus_updated',
                    'title'  => 'Syllabus Updated',
                    'intro'  => "Your {$name} syllabus has changed:",
                    'screen' => 'SyllabusDetail',
                    'params' => ['subjectId' => $subjectId, 'subjectName' => $name, 'subjectImage' => $subject?->iconUrl()],
                ], $lines);
            }
        });
    }

    /** An exam syllabus the class already had changed (chapters added / removed). */
    public function examSyllabusChanged(Exam $exam, int $standardId, ?int $sectionId, int $subjectId, array $lines): void
    {
        $this->safe(function () use ($exam, $standardId, $sectionId, $subjectId, $lines) {
            $intro = "{$exam->exam_name} syllabus for " . $this->subjectName($subjectId) . ' has changed:';
            foreach ($this->classUserIds((int) $exam->organization_id, $standardId, $sectionId) as $userId) {
                $this->queue($userId, "exam-syllabus:{$exam->id}:{$subjectId}", [
                    'type'   => 'exam_syllabus_updated',
                    'title'  => 'Exam Syllabus Updated',
                    'intro'  => $intro,
                    'screen' => 'ExamSyllabus',
                ], $lines);
            }
        });
    }

    /**
     * A class's date sheet was saved with a change — its students hear every
     * paper. A sheet for the whole class reaches the sections that have no
     * sheet of their own, as the app picks them.
     */
    public function datesheetIssued(Exam $exam, ExamDatesheet $sheet, int $standardId, ?int $sectionId, bool $isNew): void
    {
        $this->safe(function () use ($exam, $sheet, $standardId, $sectionId, $isNew) {
            $orgId = (int) $exam->organization_id;
            $students = StudentDetail::where('organization_id', $orgId)->where('standard_id', $standardId);
            if ($sectionId) {
                $students->where('section_id', $sectionId);
            } else {
                $own = ExamDatesheet::where('organization_id', $orgId)->where('exam_id', $exam->id)
                    ->where('standard_id', $standardId)->whereNotNull('section_id')->pluck('section_id');
                $students->whereNotIn('section_id', $own);
            }

            $sheet->loadMissing('papers.subject');
            $lines = $sheet->papers->sortBy('exam_date')
                ->mapWithKeys(fn ($p) => ['p:' . $p->subject_id => ($p->subject->name ?? 'Subject') . ' — '
                    . Carbon::parse($p->exam_date)->format('D, d M')
                    . ($p->start_time ? ' · ' . $this->timeRange($p->start_time, $p->end_time) : '')])
                ->all();

            foreach ($this->studentUserIds($students) as $userId) {
                $this->queue($userId, 'datesheet:' . $exam->id, [
                    'type'   => 'datesheet_issued',
                    'title'  => $isNew ? 'Date Sheet Issued' : 'Date Sheet Updated',
                    'intro'  => "{$exam->exam_name} date sheet:",
                    'screen' => 'DateSheet',
                ], $lines);
            }
        });
    }

    public function admitCardIssued(AdmitCard $card): void
    {
        $this->safe(function () use ($card) {
            $exam = Exam::find($card->exam_id);
            if ($card->status !== 'active' || !$exam || !$exam->is_published) {
                return;
            }
            $this->queue($this->userIdOfStudent((int) $card->student_detail_id), 'admit:' . $exam->id, [
                'type'   => 'admit_card_issued',
                'title'  => 'Admit Card Issued',
                'intro'  => "Your admit card for {$exam->exam_name} has been issued.",
                'screen' => 'AdmitCardScreen',
            ], ['dates' => 'Exam: ' . $this->dateRange($exam->start_date, $exam->end_date)]);
        });
    }

    /** A seating plan was published — each student seated in it hears their room and seat. */
    public function seatingPublished(int $planId): void
    {
        $this->safe(function () use ($planId) {
            $plan = SeatingPlan::find($planId);
            $exam = $plan ? Exam::find($plan->exam_id) : null;
            if (!$plan || $plan->status !== 'published' || !$exam || !$exam->is_published) {
                return;
            }
            $seats = SeatAssignment::with(['room:id,room_name', 'seat:id,seat_number,row_no,col_no'])
                ->where('seating_plan_id', $plan->id)->whereNotNull('student_id')->get();
            if ($seats->isEmpty()) {
                return;
            }
            // student_id holds the student's detail id (older plans: their user id).
            $ids = $seats->pluck('student_id')->unique()->all();
            $details = StudentDetail::where('organization_id', $plan->organization_id)
                ->where(fn ($q) => $q->whereIn('id', $ids)->orWhereIn('user_id', $ids))
                ->get(['id', 'user_id']);
            $userOf = [];
            foreach ($details as $d) {
                $userOf[(int) $d->id] ??= (int) $d->user_id;
            }
            foreach ($details as $d) {
                $userOf[(int) $d->user_id] ??= (int) $d->user_id;
            }
            $students = User::whereIn('id', array_filter($userOf))->where('role', self::STUDENT)->pluck('id')->flip();

            $when = trim(($plan->exam_date ? $plan->exam_date->format('D, d M Y') : '') . ($plan->session ? ' · ' . Str::headline((string) $plan->session) : ''), ' ·');
            foreach ($seats as $a) {
                $userId = $userOf[(int) $a->student_id] ?? null;
                if (!$userId || !isset($students[$userId])) {
                    continue;
                }
                $seat = SeatLabel::full($a->room?->room_name, $a->seat?->row_no, $a->seat?->col_no, $a->seat_position);
                $this->queue($userId, 'seating:' . $plan->id, [
                    'type'   => 'seating_published',
                    'title'  => 'Seating Plan Published',
                    'intro'  => "Your seat for {$exam->exam_name}" . ($when !== '' ? " ({$when})" : '') . ' is ready.',
                    'screen' => 'SeatingPlanScreen',
                ], $seat !== '—' ? ['seat' => 'Seat: ' . $seat] : []);
            }
        });
    }

    public function reportCardIssued(ReportCard $rc): void
    {
        $this->safe(function () use ($rc) {
            if ($rc->status !== 'issued') {
                return;
            }
            $lines = $rc->result ? ['result' => 'Result: ' . Str::headline((string) $rc->result)] : [];
            $this->queue($this->userIdOfStudent((int) $rc->student_detail_id), 'report-card:' . $rc->id, [
                'type'   => 'report_card_issued',
                'title'  => 'Report Card Issued',
                'intro'  => 'Your report card' . ($rc->academic_year ? " for {$rc->academic_year}" : '') . ' has been issued.',
                'screen' => 'ReportCardScreen',
            ], $lines);
        });
    }

    /** A student's marks in a paper were entered or changed — one push per exam, a line per subject. */
    public function marksSaved(ExamCopy $copy): void
    {
        $this->safe(function () use ($copy) {
            $fields = ['marks_obtained', 'max_marks', 'is_absent', 'grade'];
            $changed = $copy->wasRecentlyCreated
                || collect($fields)->contains(fn ($f) => $copy->wasChanged($f)
                    && $this->norm($copy->getRawOriginal($f)) !== $this->norm($copy->getAttributes()[$f] ?? null));
            $hasMarks = $copy->is_absent || $copy->marks_obtained !== null;
            if (!$changed || !$hasMarks || !Auth::check()) {
                return;
            }
            $exam = Exam::find($copy->exam_id);
            if (!$exam) {
                return;
            }

            $score = $copy->is_absent
                ? 'Absent'
                : $this->number($copy->marks_obtained) . ($copy->max_marks ? '/' . $this->number($copy->max_marks) : '')
                    . (($grade = $copy->grade_letter ?? $copy->grade) ? " · {$grade}" : '');

            $this->queue($this->userIdOfStudent((int) $copy->student_detail_id), 'marks:' . $exam->id, [
                'type'   => 'marks_uploaded',
                'title'  => 'Marks Uploaded',
                'intro'  => "{$exam->exam_name} marks:",
                'screen' => 'PerformanceScreen',
            ], ['s:' . $copy->subject_id => $this->subjectName((int) $copy->subject_id) . ": {$score}"]);
        });
    }

    // ── Contact School ────────────────────────────────────────────────────────

    public function contactReplied(ContactAdminStudent $c): void
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
                    'message'     => Str::limit((string) $c->student_query, 300),
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
            if (!isset(self::PROFILE_FIELDS[$field])) {
                continue;
            }
            $oldText = $this->profileValue($field, $old);
            $newText = $this->profileValue($field, $new);
            if ($oldText === $newText) {
                continue;
            }
            $label = self::PROFILE_FIELDS[$field];
            $lines[$label] = match (true) {
                $field === 'image'     => $newText === '' ? 'Photo removed' : 'Photo changed',
                $field === 'aadhar_no' => 'Aadhaar number updated',
                $newText === ''        => "{$label}: removed",
                $oldText === ''        => "{$label}: {$newText}",
                default                => "{$label}: {$oldText} → {$newText}",
            };
        }
        if (!$lines) {
            return;
        }
        $this->queue($userId, 'profile', [
            'type'   => 'profile_updated',
            'title'  => 'Profile Updated',
            'intro'  => 'The school updated your profile:',
            'screen' => 'StudentProfile',
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
        $detail = DB::table('student_details')->where('user_id', $userId)->first();
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
        if ($value === '' && $field !== 'transportation_required') {
            return '';
        }

        return match ($field) {
            'dob', 'date_of_admission' => $this->day($value) ? Carbon::parse($value)->format('d M Y') : $value,
            'gender'                   => ucfirst($value),
            'is_active'                => $value === '1' ? 'Active' : 'Inactive',
            'transportation_required'  => $value === '1' ? 'Yes' : 'No',
            'standard_id'              => (int) $value ? ($this->standardName((int) $value) ?? $value) : '',
            'section_id'               => (int) $value ? ($this->sectionName((int) $value) ?? $value) : '',
            'image', 'aadhar_no'       => $value,
            default                    => Str::limit($value, 60),
        };
    }

    private function receiptLines($receipt, $mode, $date): array
    {
        $lines = [];
        if ($receipt) {
            $lines['receipt'] = "Receipt: {$receipt}";
        }
        if ($mode) {
            $lines['mode'] = 'Mode: ' . (strtolower((string) $mode) === 'upi' ? 'UPI' : Str::headline((string) $mode));
        }
        if ($date) {
            $lines['date'] = 'Date: ' . Carbon::parse($date)->format('d M Y');
        }

        return $lines;
    }

    /** A payment booked by approving a school-QR payment — that approval sends its own push. */
    private function fromSchoolQr($remark): bool
    {
        return str_starts_with((string) $remark, 'Paid on school QR');
    }

    /** One organisation's reminders for the installments due on the reminder days. */
    private function remindOrg(int $orgId, Collection $dueCycles, array $stageOf, Carbon $today): int
    {
        // The installments as the app's Fees screen lays them out: every active
        // academic cycle in order, the token fee first off the top.
        $cycles = FeeCycle::where('organization_id', $orgId)->where('is_active', true)
            ->where('fee_type', 'academic')->orderBy('payment_serial')->get();
        $settings = FeeSettings::where('organization_id', $orgId)->first();
        $tokenAmt = (float) optional($cycles->firstWhere('is_token', true))->amount;

        $students = StudentDetail::where('organization_id', $orgId)->whereNotNull('user_id')
            ->get(['id', 'user_id', 'standard_id', 'section_id']);
        $active = User::whereIn('id', $students->pluck('user_id'))->where('role', self::STUDENT)->pluck('id')->flip();
        $structures = FeeStructure::where('organization_id', $orgId)->where('is_active', true)
            ->where('fee_type', 'academic')->get(['standard_id', 'section_id', 'amount']);
        $paid = FeePayment::where('organization_id', $orgId)->where('fee_type', 'academic')
            ->selectRaw('student_detail_id, SUM(amount) as paid')->groupBy('student_detail_id')
            ->pluck('paid', 'student_detail_id');
        $concessions = FeeConcession::where('organization_id', $orgId)->whereIn('fee_type', ['academic', 'all'])
            ->get()->groupBy('student_detail_id');

        $sent = 0;
        foreach ($students as $student) {
            if (!isset($active[$student->user_id])) {
                continue;
            }
            $academicDue = (float) $structures->where('standard_id', $student->standard_id)
                ->filter(fn ($s) => !$s->section_id || (int) $s->section_id === (int) $student->section_id)
                ->sum('amount');
            if ($academicDue <= 0) {
                continue;
            }
            $concession = min($academicDue, round((float) collect($concessions->get($student->id, []))
                ->sum(fn ($c) => $c->discountOn($academicDue)), 2));
            $base = max(0, $academicDue - $concession - $tokenAmt);
            $left = (float) ($paid[$student->id] ?? 0);

            foreach ($cycles as $cycle) {
                $amount = $cycle->fee_percent > 0 ? round($base * (float) $cycle->fee_percent / 100, 2) : (float) $cycle->amount;
                $covered = min($left, $amount);
                $left = max(0, $left - $covered);
                $outstanding = round($amount - $covered, 2);

                if ($outstanding <= 0 || !$dueCycles->has($cycle->id)) {
                    continue;
                }
                $days = $stageOf[$cycle->due_date->toDateString()];
                // Once a day per student, installment and reminder, however often it runs.
                $key = "fee-reminder:{$student->id}:{$cycle->id}:{$days}:" . $today->toDateString();
                if (!$this->attempt(fn () => Cache::add($key, 1, now()->addDays(2))) && $this->attempt(fn () => Cache::has($key))) {
                    continue;
                }
                $rate = (float) ($cycle->penalty_per_day > 0 ? $cycle->penalty_per_day : ($settings->penalty_per_day ?? 0));
                $this->queue((int) $student->user_id, 'fee-due:' . $cycle->id, $this->reminder($cycle, $days, $outstanding, $rate), []);
                $sent++;
            }
        }

        return $sent;
    }

    /** The reminder for an installment $days before its due date (-1: a day late). */
    private function reminder(FeeCycle $cycle, int $days, float $outstanding, float $rate): array
    {
        $what = ($cycle->is_token ? 'token fee' : $this->ordinal((int) $cycle->payment_serial) . ' installment')
            . ' of ' . $this->rupees($outstanding);
        $date = $cycle->due_date->format('d M Y');
        $late = $rate > 0 ? 'A late fee of ' . $this->rupees($rate) . ' per day applies after the due date.' : '';

        [$title, $intro] = match (true) {
            $days < 0   => ['Fee Overdue', "Your {$what} was due on {$date} and is still unpaid. "
                . ($rate > 0 ? 'Penalty has started: ' . $this->rupees($rate) . ' per day until it is paid.' : 'Please pay it as soon as possible.')],
            $days === 0 => ['Fee Due Today', "Your {$what} is due today. Please pay it today. {$late}"],
            $days === 1 => ['Fee Due Tomorrow', "Your {$what} is due tomorrow ({$date}). Please pay it on time. {$late}"],
            default     => ["Fee Due in {$days} Days", "Your {$what} is due on {$date}. Please pay it before the due date. {$late}"],
        };

        return ['type' => $days < 0 ? 'fee_overdue' : 'fee_due', 'title' => $title, 'intro' => trim($intro), 'screen' => 'Fees'];
    }

    private function ordinal(int $n): string
    {
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');

        return $n . $suffix;
    }

    /** The student accounts in a class (a section of it, or all of it). */
    private function classUserIds(int $orgId, int $standardId, ?int $sectionId): array
    {
        if (!$orgId || !$standardId) {
            return [];
        }

        return $this->studentUserIds(StudentDetail::where('organization_id', $orgId)->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId)));
    }

    /** @return array<int> the student accounts behind a student_details query */
    private function studentUserIds($details): array
    {
        $ids = $details->whereNotNull('user_id')->pluck('user_id');

        return User::whereIn('id', $ids)->where('role', self::STUDENT)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function userIdOfStudent(int $detailId): ?int
    {
        return $detailId ? ((int) StudentDetail::whereKey($detailId)->value('user_id') ?: null) : null;
    }

    /** "07:30" / "7:30 AM" → "7:30 AM", or the time as it was typed. */
    private function clock(string $time): string
    {
        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable) {
            return $time;
        }
    }

    /** 45.00 → "45", 45.50 → "45.5" */
    private function number($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
