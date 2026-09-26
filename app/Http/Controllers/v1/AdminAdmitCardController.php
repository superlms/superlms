<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Admin\AdmitCardController as WebAdmitCardController;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamDatesheet;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\AdmitCard as ModelAdmitCard;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * School-admin Admit Card module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/AdmitCard.php — pick an exam + class (+ section) to
 * list every student coloured by whether a card is issued, issue one student,
 * bulk-generate by attendance/fee criteria, view a card and delete. The subject
 * schedule comes from the exam datesheet; seat/room resolve from the seating
 * plan at PDF time. PDF generation delegates to the web controller (same blade),
 * scoped by the authenticated user's organization.
 *
 * Printing is the web's too: the Print panel's list (not yet printed, or all
 * as a reprint), Print stamping the cards as printed and handing back the
 * four-up sheet as a PDF, and a printed card queued again for the next run.
 */
class AdminAdmitCardController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    // ══════════════════════════ LOOKUPS ══════════════════════════

    /**
     * GET /admin/admit-card/lookups — exams + classes (with sections).
     *
     * Each exam also carries what the student app's exam rows read (term, type,
     * dates, status, marks — as ExamController::formatExam gives them) and how
     * many cards it has issued, so the admin app can list the exams the way a
     * student's Admit Card screen does.
     */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $issuedByExam = ModelAdmitCard::where('organization_id', $orgId)
            ->selectRaw('exam_id, COUNT(*) AS c')->groupBy('exam_id')
            ->pluck('c', 'exam_id');

        $now = now();
        $exams = Exam::where('organization_id', $orgId)
            ->orderByDesc('start_date')->get()
            ->map(fn ($e) => [
                'id'            => $e->id,
                'name'          => $e->exam_name,
                'academic_year' => $e->academic_year,
                'exam_name'     => $e->exam_name,
                'term'          => $e->term,
                'exam_type'     => $e->exam_type,
                'start_date'    => $e->start_date?->format('Y-m-d'),
                'end_date'      => $e->end_date?->format('Y-m-d'),
                'status'        => match (true) {
                    $e->start_date > $now => 'upcoming',
                    $e->end_date   < $now => 'completed',
                    default               => 'ongoing',
                },
                'total_marks'   => $e->total_marks,
                'passing_marks' => $e->passing_marks,
                'issued'        => (int) ($issuedByExam[$e->id] ?? 0),
            ]);

        $classes = Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'sections' => Section::where('standard_id', $s->id)->where('organization_id', $orgId)
                    ->orderBy('id')->get(['id', 'name'])->toArray(),
            ]);

        return $this->success(['exams' => $exams, 'classes' => $classes], 'Admit card lookups fetched.');
    }

    /**
     * GET /admin/admit-card/classes?exam_id=
     *
     * One exam's classes, each with its sections, and for every class and
     * section how many students it has and how many of them hold this exam's
     * card — the web page's Total / Issued / Remaining, counted the same way
     * (students by their class and section now, cards by the class and
     * section they were issued in), one line per class instead of one filter
     * at a time.
     */
    public function classes(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'exam_id' => 'required|integer',
        ], [
            'exam_id.required' => 'Select an exam first.',
        ])) return $err;

        $examId = (int) $request->exam_id;
        if (!Exam::where('organization_id', $orgId)->whereKey($examId)->exists()) {
            return $this->error('Exam not found.', 404);
        }

        $students = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('standard_id, section_id, COUNT(*) AS c')
            ->groupBy('standard_id', 'section_id')->get();
        $cards = ModelAdmitCard::where('organization_id', $orgId)->where('exam_id', $examId)
            ->selectRaw('standard_id, section_id, COUNT(*) AS c')
            ->groupBy('standard_id', 'section_id')->get();

        $count = fn ($rows, $std, $sec = false) => (int) $rows
            ->filter(fn ($r) => (int) $r->standard_id === (int) $std
                && ($sec === false || (int) $r->section_id === (int) $sec))
            ->sum('c');

        $classes = Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'students' => $count($students, $s->id),
                'issued'   => $count($cards, $s->id),
                'sections' => Section::where('standard_id', $s->id)->where('organization_id', $orgId)
                    ->orderBy('id')->get(['id', 'name'])
                    ->map(fn ($sec) => [
                        'id'       => $sec->id,
                        'name'     => $sec->name,
                        'students' => $count($students, $s->id, $sec->id),
                        'issued'   => $count($cards, $s->id, $sec->id),
                    ])->values(),
            ])->values();

        $total  = StudentDetail::where('organization_id', $orgId)->count();
        $issued = ModelAdmitCard::where('organization_id', $orgId)->where('exam_id', $examId)->count();

        return $this->success([
            'classes' => $classes,
            'totals'  => [
                'total'     => $total,
                'issued'    => $issued,
                'remaining' => max(0, $total - $issued),
            ],
        ], 'Admit card classes fetched.');
    }

    // ══════════════════════════ LIST + ANALYTICS ══════════════════════════

    /**
     * GET /admin/admit-card?exam_id=&standard_id=&section_id=&search=&status=&per_page=&page=
     * Requires exam_id + standard_id (mirrors the web "ready" gate).
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'exam_id'     => 'required|integer',
            'standard_id' => 'required|integer',
        ], [
            'exam_id.required'     => 'Select an exam first.',
            'standard_id.required' => 'Select a class first.',
        ])) return $err;

        $examId = (int) $request->exam_id;
        $standardId = (int) $request->standard_id;
        $sectionId = $request->filled('section_id') ? (int) $request->section_id : null;
        $status = $request->input('status'); // '' | issued | not_issued
        $search = $request->input('search');

        $query = StudentDetail::with(['standard:id,name', 'section:id,name', 'user:id,image'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->when($search, fn ($q) => $q->where(fn ($s) =>
                $s->where('full_name', 'like', "%{$search}%")
                  ->orWhere('roll_no', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%")))
            ->when($status === 'issued', fn ($q) =>
                $q->whereHas('admitCards', fn ($a) => $a->where('exam_id', $examId)))
            ->when($status === 'not_issued', fn ($q) =>
                $q->whereDoesntHave('admitCards', fn ($a) => $a->where('exam_id', $examId)))
            ->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no');

        $paginator = $query->paginate((int) $request->input('per_page', 15));

        $issued = ModelAdmitCard::where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->whereIn('student_detail_id', collect($paginator->items())->pluck('id'))
            ->get()->keyBy('student_detail_id');

        $items = collect($paginator->items())->map(function ($student) use ($issued) {
            $card = $issued->get($student->id);
            return [
                'id'           => $student->id,
                'full_name'    => $student->full_name,
                'roll_no'      => $student->roll_no,
                'admission_no' => $student->admission_no,
                'standard'     => $student->standard?->name,
                'section'      => $student->section?->name,
                'image'        => $student->user?->image ?? null,
                'issued'       => (bool) $card,
                'admit_card_id'=> $card?->id,
                // The web list's Card No. and its Printed / Not printed line.
                'admit_card_number' => $card?->admit_card_number,
                'printed_at'   => $card?->printed_at?->format('d M Y, g:i A'),
            ];
        });

        return $this->paginated($items, $this->paginationMeta($paginator), 'Admit card students fetched.');
    }

    /** GET /admin/admit-card/analytics?exam_id=&standard_id=&section_id= */
    public function analytics(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $studentQuery = StudentDetail::where('organization_id', $orgId);
        if ($request->filled('standard_id')) $studentQuery->where('standard_id', $request->standard_id);
        if ($request->filled('section_id'))  $studentQuery->where('section_id', $request->section_id);
        $total = $studentQuery->count();

        $issued = 0;
        if ($request->filled('exam_id')) {
            $cardQuery = ModelAdmitCard::where('organization_id', $orgId)->where('exam_id', $request->exam_id);
            if ($request->filled('standard_id')) $cardQuery->where('standard_id', $request->standard_id);
            if ($request->filled('section_id'))  $cardQuery->where('section_id', $request->section_id);
            $issued = $cardQuery->count();
        }

        return $this->success([
            'total'     => $total,
            'issued'    => $issued,
            'remaining' => max(0, $total - $issued),
        ], 'Admit card analytics fetched.');
    }

    // ══════════════════════════ VIEW A CARD ══════════════════════════

    /** GET /admin/admit-card/{id} — the stored card, rendered student-style in-app. */
    public function show($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $card = ModelAdmitCard::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'organization'])
            ->where('organization_id', $user->organization_id)->find($id);
        if (!$card) return $this->error('Admit card not found.', 404);

        $seating = null;
        if ($card->room_number && $card->seat_number) $seating = 'R(' . $card->room_number . ')/ S(' . $card->seat_number . ')';
        elseif ($card->seat_number) $seating = 'S(' . $card->seat_number . ')';
        elseif ($card->room_number) $seating = 'R(' . $card->room_number . ')';

        $org = $card->organization;

        return $this->success(['card' => [
            'id'                => $card->id,
            'admit_card_number' => $card->admit_card_number,
            'issue_date'        => optional($card->issue_date)->format('d M Y'),
            'exam_name'         => $card->exam_name,
            'academic_year'     => $card->academic_year,
            'student' => [
                'full_name'   => $card->student_name,
                'father_name' => $card->father_name,
                'mother_name' => $card->mother_name,
                'roll_number' => $card->roll_number,
                'class'       => trim(($card->studentDetail?->standard?->name ?? '') . ' ' . ($card->studentDetail?->section?->name ?? '')),
                'admission_no'=> $card->studentDetail?->admission_no,
                'image_url'   => $card->studentDetail?->user?->image ?? null,
            ],
            'subjects'    => collect($card->subjects ?? [])->map(fn ($s) => [
                'subject_name'  => $s['subject_name'] ?? '',
                'exam_date'     => $s['exam_date'] ?? '',
                'exam_time'     => $s['exam_time'] ?? '',
                'exam_duration' => $s['exam_duration'] ?? '',
            ])->values(),
            'seating_label' => $seating,
            'exam_center'   => $card->exam_center,
            'organization'  => [
                'name'    => $org?->name,
                'address' => $org?->address,
                'logo'    => $org?->logo ?? null,
            ],
            'pdf_url' => url("/api/v1/admin/admit-card/{$card->id}/pdf"),
            'exam_id'           => $card->exam_id,
            'student_detail_id' => $card->student_detail_id,
            'standard_id'       => $card->standard_id,
            'section_id'        => $card->section_id,
            'printed_at'        => $card->printed_at?->format('d M Y, g:i A'),
        ]], 'Admit card fetched.');
    }

    /** GET /admin/admit-card/{id}/pdf — streams the same blade PDF as the web admin. */
    public function pdf(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!ModelAdmitCard::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Admit card not found.', 404);
        }

        return app(WebAdmitCardController::class)->download($request, $user->organization_id, $id);
    }

    // ══════════════════════════ PRINT (web parity) ══════════════════════════

    /**
     * GET /admin/admit-card/printable?exam_id=&standard_id=&section_id=&include_done=
     *
     * The web Print panel's list: the cards issued for the exam + class
     * (+ section), by default only those never printed — so a second run after
     * a second batch of issues brings just the new ones — and how many of that
     * class are already printed (the panel offers them as a reprint).
     */
    public function printable(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'exam_id'     => 'required|integer',
            'standard_id' => 'required|integer',
        ], [
            'exam_id.required'     => 'Choose the exam you are printing for.',
            'standard_id.required' => 'Choose the class you are printing for.',
        ])) return $err;

        $sectionId   = $request->filled('section_id') ? (int) $request->section_id : null;
        $includeDone = $request->boolean('include_done');

        $base = fn () => ModelAdmitCard::where('organization_id', $orgId)
            ->where('exam_id', (int) $request->exam_id)
            ->where('standard_id', (int) $request->standard_id)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId));

        $cards = $base()
            ->when(!$includeDone, fn ($q) => $q->unprinted())
            ->orderByRaw('CAST(roll_number AS UNSIGNED), roll_number')
            ->get()
            ->map(fn ($c) => [
                'id'                => $c->id,
                'student_name'      => $c->student_name,
                'roll_number'       => $c->roll_number,
                'admit_card_number' => $c->admit_card_number,
                'printed_at'        => $c->printed_at?->format('d M Y, g:i A'),
            ])->values();

        return $this->success([
            'cards'           => $cards,
            'already_printed' => $base()->whereNotNull('printed_at')->count(),
        ], 'Printable admit cards fetched.');
    }

    /**
     * POST /admin/admit-card/print — { ids: [..] }
     *
     * The web's Print (a selection) and Print this card (one): the cards are
     * stamped as printed, so the next run only brings the ones issued since,
     * and the four-up sheet comes back as a PDF link.
     */
    public function printCards(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return $this->error('Select at least one student to print.', 422);
        }

        $ids = ModelAdmitCard::where('organization_id', $orgId)->whereIn('id', $ids)->pluck('id');
        if ($ids->isEmpty()) {
            return $this->error('Those admit cards no longer exist.', 404);
        }

        ModelAdmitCard::where('organization_id', $orgId)->whereIn('id', $ids)
            ->update(['printed_at' => now()]);

        return $this->success([
            'count'   => $ids->count(),
            'ids'     => $ids,
            'pdf_url' => url('/api/v1/admin/admit-card/sheet') . '?' . http_build_query(['ids' => $ids->implode(',')]),
        ], $ids->count() === 1 ? 'Admit card ready to print.' : "{$ids->count()} admit cards ready to print.");
    }

    /** GET /admin/admit-card/sheet?ids= — the four-up print sheet as a PDF (the web's print-all). */
    public function sheet(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return app(WebAdmitCardController::class)->sheetPdf($request, $user->organization_id);
    }

    /** POST /admin/admit-card/{id}/unprinted — undo the printed stamp, so it comes out on the next run. */
    public function markUnprinted($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $card = ModelAdmitCard::where('organization_id', $user->organization_id)->find($id);
        if (!$card) return $this->error('Admit card not found.', 404);

        $card->update(['printed_at' => null]);
        return $this->success(null, 'That card will print on the next run.');
    }

    // ══════════════════════════ ISSUE ONE ══════════════════════════

    /** POST /admin/admit-card/issue — { exam_id, student_id }. */
    public function issueOne(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'exam_id'    => 'required|exists:exams,id',
            'student_id' => 'required|integer',
        ])) return $err;

        $orgId = $user->organization_id;
        $exam    = Exam::where('organization_id', $orgId)->find($request->exam_id);
        $student = StudentDetail::where('organization_id', $orgId)->find($request->student_id);
        if (!$exam || !$student) return $this->error('Exam or student not found.', 404);

        if ($this->createCardFor($student, $exam, $user->id)) {
            return $this->success(null, "Admit card issued for {$student->full_name}.", 201);
        }
        return $this->success(['already' => true], 'This student already has an admit card for this exam.');
    }

    // ══════════════════════════ GENERATE (BULK) ══════════════════════════

    /**
     * POST /admin/admit-card/generate
     * { exam_id, standard_id, section_id?, criteria: none|attendance|fee, percentage? }
     */
    public function generate(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'exam_id'     => 'required|exists:exams,id',
            'standard_id' => 'required|exists:standards,id',
            'section_id'  => 'nullable|integer',
            'criteria'    => 'nullable|in:none,attendance,fee',
            'percentage'  => 'required_unless:criteria,none|nullable|integer|min:1|max:100',
        ])) return $err;

        $orgId = $user->organization_id;
        $criteria = $request->input('criteria', 'none');
        $percentage = (int) $request->input('percentage', 75);
        $exam = Exam::where('organization_id', $orgId)->findOrFail($request->exam_id);

        $students = StudentDetail::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
            ->whereDoesntHave('admitCards', fn ($q) => $q->where('exam_id', $request->exam_id))
            ->get();

        $eligible = $students->filter(fn ($student) => match ($criteria) {
            'attendance' => $this->meetsAttendanceCriteria($student->id, $percentage),
            'fee'        => $this->meetsFeeCriteria($student, $orgId, $percentage),
            default      => true,
        });

        $generated = 0;
        foreach ($eligible as $student) {
            if ($this->createCardFor($student, $exam, $user->id)) $generated++;
        }
        $skipped = $students->count() - $generated;

        return $this->success([
            'generated' => $generated,
            'skipped'   => $skipped,
        ], "Issued {$generated} admit card(s)." . ($skipped > 0 ? " {$skipped} did not meet the criteria." : ''), 201);
    }

    // ══════════════════════════ DELETE ══════════════════════════

    /** DELETE /admin/admit-card/{id} */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $card = ModelAdmitCard::where('organization_id', $user->organization_id)->find($id);
        if (!$card) return $this->error('Admit card not found.', 404);

        $card->delete();
        return $this->success(null, 'Admit card removed — student is back in the not-issued list.');
    }

    // ══════════════════════════ INTERNALS (ported from Livewire) ══════════════════════════

    private function createCardFor(StudentDetail $student, Exam $exam, int $userId): bool
    {
        if (ModelAdmitCard::where('student_detail_id', $student->id)->where('exam_id', $exam->id)->exists()) {
            return false;
        }

        $orgId = $student->organization_id;
        $subjects = $this->subjectsFromDatesheet($orgId, $exam->id, (int) $student->standard_id, $student->section_id ? (int) $student->section_id : null);

        ModelAdmitCard::create([
            'student_detail_id'   => $student->id,
            'exam_id'             => $exam->id,
            'organization_id'     => $orgId,
            'admit_card_number'   => ModelAdmitCard::generateAdmitCardNumber($orgId, $exam->id),
            'student_name'        => $student->full_name,
            'father_name'         => $student->father_name,
            'mother_name'         => $student->mother_name,
            'roll_number'         => $student->roll_no ?? 'N/A',
            'standard_id'         => $student->standard_id,
            'section_id'          => $student->section_id,
            'exam_name'           => $exam->exam_name,
            'academic_year'       => $exam->academic_year,
            'reporting_time'      => null,
            'exam_center'         => '',
            'exam_center_address' => '',
            'instructions'        => '',
            'allowed_items'       => [],
            'prohibited_items'    => [],
            'subjects'            => $subjects,
            'status'              => 'active',
            'issue_date'          => now(),
            'created_by'          => $userId,
        ]);

        return true;
    }

    private function subjectsFromDatesheet(int $orgId, int $examId, int $standardId, ?int $sectionId): array
    {
        $base = ExamDatesheet::with('papers.subject')
            ->where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->where('standard_id', $standardId);

        $ds = null;
        if ($sectionId) {
            $ds = (clone $base)->where('section_id', $sectionId)->first();
        }
        $ds ??= (clone $base)->whereNull('section_id')->first() ?? (clone $base)->first();

        if ($ds && $ds->papers->isNotEmpty()) {
            return $ds->papers->map(fn ($p) => [
                'subject_id'    => (string) $p->subject_id,
                'subject_name'  => $p->subject->name ?? '',
                'exam_date'     => optional($p->exam_date)->format('Y-m-d') ?? '',
                'exam_time'     => $p->start_time ? Carbon::parse($p->start_time)->format('H:i') : '',
                'exam_end_time' => $p->end_time ? Carbon::parse($p->end_time)->format('H:i') : '',
                'exam_duration' => $this->durationLabel($p->start_time, $p->end_time),
                'shift'         => $p->shift,
                'status'        => 'eligible',
            ])->values()->toArray();
        }

        $q = SectionSubject::where('organization_id', $orgId)->where('standard_id', $standardId);
        if ($sectionId) $q->where('section_id', $sectionId);
        $subjects = Subject::whereIn('id', $q->pluck('subject_id')->unique())
            ->where('is_active', true)->orderBy('id')->get();

        return $subjects->map(fn ($s) => [
            'subject_id'    => (string) $s->id,
            'subject_name'  => $s->name,
            'exam_date'     => '',
            'exam_time'     => '',
            'exam_end_time' => '',
            'exam_duration' => '',
            'shift'         => 1,
            'status'        => 'eligible',
        ])->toArray();
    }

    private function durationLabel($start, $end): string
    {
        if (!$start || !$end) return '';
        try {
            $mins = Carbon::parse($end)->diffInMinutes(Carbon::parse($start));
            if ($mins <= 0) return '';
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            $label = $h ? $h . ($h === 1 ? ' Hour' : ' Hours') : '';
            return trim($label . ($m ? " {$m} Min" : ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function meetsAttendanceCriteria(int $studentId, int $percentage): bool
    {
        $total = StudentAttendance::where('student_detail_id', $studentId)->count();
        if ($total === 0) return true;
        $present = StudentAttendance::where('student_detail_id', $studentId)->where('status', 1)->count();
        return ($present / $total * 100) >= $percentage;
    }

    private function meetsFeeCriteria(StudentDetail $student, int $orgId, int $percentage): bool
    {
        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('standard_id', $student->standard_id)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $student->section_id))
            ->get()
            // …and the student's own — Last Year Dues.
            ->concat(FeeStructure::ownRows($orgId, [$student->id], null));

        $academic  = $structures->where('fee_type', 'academic')->sum('amount');
        $transport = $student->transportation_required
            ? $structures->where('fee_type', 'transport')->sum('amount')
            : 0;
        $totalFee = $academic + $transport;
        if ($totalFee <= 0) return true;

        $paid = FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)->sum('amount');

        return ($paid / $totalFee * 100) >= $percentage;
    }
}
