<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamDatesheet;
use App\Models\Admin\ExamDatesheetPaper;
use App\Models\Admin\Seating\InvigilatorAssignment;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingInvigilator;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Admin\Seating\SeatingRoom;
use App\Models\Admin\Seating\SeatingSeat;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Services\Seating\GeneratedSeating;
use App\Services\Seating\SeatLocator;
use App\Services\Seating\SeatingPlannerService;
use App\Support\ModuleAccess;
use App\Support\SeatLabel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The admin app's Seating Plan — the web panel's Exam Seating Management
 * (App\Livewire\Admin\SeatingPlan) over the same tables, tab for tab:
 *
 *   Seating Plans  the seat finder — an exam, then a room, or a class and a
 *                  section — listing each generated session with its paper,
 *                  date, shift and candidates; the session's seating list as
 *                  the panel's PDF, each room's chart, Publish and Delete; and
 *                  Generate Plan, one plan per exam date and shift read off the
 *                  datesheet.
 *   Rooms          rooms with rows × columns desks and seats per desk (seats
 *                  are rebuilt from them on every save), active or not.
 *   Datesheet      an exam → class → section's sheet (the section's own, else
 *                  the class-wide one), created and edited paper by paper with
 *                  the panel's clash check, printed as a PDF.
 *
 * Generate and the datesheet's save and clash check are copies of the panel's
 * own code, which was left untouched — a change to one must be made in both.
 * The PDFs are the panel's own controllers and views.
 */
class AdminSeatingController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /** The panel's route, for the school's modules and a sub-admin's permissions. */
    private const PANEL_ROUTE = 'admin.seating-plan';

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        if (!ModuleAccess::allows($user->organization, self::PANEL_ROUTE)) {
            return [null, $this->error('This feature is not enabled for your school. Please contact your administrator.', 403)];
        }
        if (!$user->canAccessAdminRoute(self::PANEL_ROUTE)) {
            return [null, $this->error('You do not have access to Seating Plan.', 403)];
        }
        return [$user, null];
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  OVERVIEW & LOOKUPS
    // ═══════════════════════════════════════════════════════════════════════

    /** GET /admin/seating/overview — the panel header's counts. */
    public function overview()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        return $this->success([
            'rooms'      => SeatingRoom::where('organization_id', $orgId)->count(),
            'plans'      => SeatingPlan::where('organization_id', $orgId)->count(),
            'datesheets' => ExamDatesheet::where('organization_id', $orgId)->count(),
        ], 'Seating overview fetched.');
    }

    /** GET /admin/seating/lookups — the exams, classes and sections the filters offer. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $standards = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->inClassOrder()->get(['id', 'name']);

        return $this->success([
            'exams' => Exam::where('organization_id', $orgId)->orderBy('start_date', 'desc')
                ->get(['id', 'exam_name', 'academic_year', 'exam_type', 'start_date'])
                ->map(fn ($e) => [
                    'id'            => $e->id,
                    'exam_name'     => $e->exam_name,
                    'academic_year' => $e->academic_year,
                ])->values(),
            'standards' => $standards->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'sections'  => Section::whereIn('standard_id', $standards->pluck('id'))->where('is_active', true)
                ->orderBy('id')->get(['id', 'name', 'standard_id'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'standard_id' => $s->standard_id])->values(),
        ], 'Seating lookups fetched.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  SEATING PLANS — the seat finder
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * GET /admin/seating/finder?mode=room|class&exam_id=&room_id=&standard_id=&section_id=
     *
     * The rooms that hold a seat in the exam (for "By Room"), and — once the
     * last filter is picked — one row per session, as the panel lists them.
     */
    public function finder(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $mode       = $request->input('mode') === 'class' ? 'class' : 'room';
        $examId     = (int) $request->input('exam_id');
        $roomId     = (int) $request->input('room_id');
        $standardId = (int) $request->input('standard_id');
        $sectionId  = (int) $request->input('section_id');

        $examPlans = $examId
            ? SeatingPlan::where('organization_id', $orgId)->where('exam_id', $examId)
                ->orderBy('exam_date')->orderBy('id')
                ->get(['id', 'name', 'exam_date', 'session', 'notes', 'status', 'total_students', 'total_seats', 'conflict_count'])
            : collect();

        // A plan made again for the same session lists once — the newest copy.
        if ($examPlans->isNotEmpty()) {
            $current   = GeneratedSeating::currentPlanIds($examPlans);
            $examPlans = $examPlans->filter(fn ($p) => in_array((int) $p->id, $current, true))->values();
        }

        // Rooms that actually hold a seat in this exam — no point offering the rest.
        $roomOptions = $examPlans->isEmpty() ? collect() : SeatingRoom::whereIn(
            'id',
            SeatAssignment::whereIn('seating_plan_id', $examPlans->pluck('id'))->distinct()->pluck('room_id')
        )->orderBy('room_name')->get(['id', 'room_name', 'building']);

        $ready = $examId && ($mode === 'room' ? (bool) $roomId : $standardId && $sectionId);
        $rows  = collect();

        if ($ready && $examPlans->isNotEmpty()) {
            $titles = $mode === 'class' ? $this->paperTitles($orgId, $examId, $standardId, $sectionId) : [];
            $times  = $mode === 'room' ? $this->paperTimes($orgId, $examId) : [];

            $query = SeatAssignment::whereIn('seating_plan_id', $examPlans->pluck('id'))->whereNotNull('student_id');
            $seated = ($mode === 'room'
                ? $query->where('room_id', $roomId)
                : $this->classLabelFilter($query, $orgId, $standardId, $sectionId)
            )->get(['id', 'seating_plan_id', 'room_id', 'student_id', 'class_label']);

            $roomNames = SeatingRoom::whereIn('id', $seated->pluck('room_id')->unique())->pluck('room_name', 'id');

            $rows = $seated->groupBy('seating_plan_id')
                ->map(function ($group, $planId) use ($examPlans, $titles, $times, $roomNames) {
                    $plan = $examPlans->firstWhere('id', (int) $planId);
                    if (!$plan) return null;

                    $date  = $plan->exam_date?->toDateString() ?? '';
                    $shift = SeatLocator::shiftOf($plan->session);

                    return [
                        'plan_id'   => (int) $planId,
                        'name'      => $plan->name,
                        'status'    => $plan->status,
                        'date'      => $plan->exam_date?->toDateString(),
                        'session'   => $plan->session ?: 'Shift 1',
                        'time'      => $this->formatTimeRange($times[$date . '|' . $shift] ?? null),
                        'subject'   => $titles[$date . '|' . $shift]
                            ?? ($plan->notes ? Str::after($plan->notes, 'Subjects: ') : 'Paper'),
                        'students'  => $group->count(),
                        'conflicts' => (int) $plan->conflict_count,
                        'classes'   => $group->pluck('class_label')->filter()->unique()->sort()->values()->all(),
                        'rooms'     => $group->pluck('room_id')->unique()->map(fn ($id) => $roomNames[$id] ?? '—')->sort()->values()->all(),
                    ];
                })
                ->filter()
                ->sortBy(fn ($row) => ($row['date'] ?? '9999-12-31') . '|' . str_pad((string) $row['plan_id'], 10, '0', STR_PAD_LEFT))
                ->values();
        }

        return $this->success([
            'mode'         => $mode,
            'ready'        => (bool) $ready,
            'has_plans'    => $examPlans->isNotEmpty(),
            'room_options' => $roomOptions->map(fn ($r) => ['id' => $r->id, 'room_name' => $r->room_name, 'building' => $r->building])->values(),
            'sessions'     => $rows,
        ], 'Seating sessions fetched.');
    }

    /** The class the filters name, as the planner writes it on an assignment ("10-A"). */
    private function classLabelFilter($query, int $orgId, int $standardId, int $sectionId)
    {
        $standard = $standardId ? Standard::where('organization_id', $orgId)->find($standardId) : null;
        if (!$standard) return $query;

        if ($sectionId) {
            $section = Section::find($sectionId);
            return $query->where('class_label', $standard->name . '-' . ($section->name ?? '-'));
        }

        return $query->where('class_label', 'like', $standard->name . '-%');
    }

    /** Which paper the chosen class sits in each session, keyed "Y-m-d|shift". */
    private function paperTitles(int $orgId, int $examId, int $standardId, int $sectionId): array
    {
        $sheets = ExamDatesheet::with('papers.subject:id,name')
            ->where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('section_id')->orWhere('section_id', $sectionId)))
            ->orderByRaw('section_id IS NULL')   // the section's own sheet wins
            ->get();

        $titles = [];
        foreach ($sheets as $sheet) {
            foreach ($sheet->papers as $paper) {
                if (empty($paper->exam_date)) continue;
                $key = Carbon::parse($paper->exam_date)->toDateString() . '|' . (int) ($paper->shift ?: 1);
                $titles[$key] ??= $paper->subject->name ?? 'Paper';
            }
        }

        return $titles;
    }

    /** The clock time each session runs: the earliest start and latest end of its papers. */
    private function paperTimes(int $orgId, int $examId): array
    {
        $papers = ExamDatesheetPaper::query()
            ->join('exam_datesheets as d', 'd.id', '=', 'exam_datesheet_papers.exam_datesheet_id')
            ->where('d.organization_id', $orgId)
            ->where('d.exam_id', $examId)
            ->whereNotNull('exam_datesheet_papers.exam_date')
            ->get([
                'exam_datesheet_papers.exam_date', 'exam_datesheet_papers.start_time',
                'exam_datesheet_papers.end_time', 'exam_datesheet_papers.shift',
            ]);

        $times = [];
        foreach ($papers as $p) {
            if (!$p->start_time && !$p->end_time) continue;
            $key = Carbon::parse($p->exam_date)->toDateString() . '|' . (int) ($p->shift ?: 1);
            $times[$key] ??= ['start' => null, 'end' => null];
            if ($p->start_time && (!$times[$key]['start'] || $p->start_time < $times[$key]['start'])) {
                $times[$key]['start'] = $p->start_time;
            }
            if ($p->end_time && (!$times[$key]['end'] || $p->end_time > $times[$key]['end'])) {
                $times[$key]['end'] = $p->end_time;
            }
        }

        return $times;
    }

    /** "10:00 AM – 1:00 PM", or whatever half of that is known. */
    private function formatTimeRange(?array $range): string
    {
        if (!$range || (!$range['start'] && !$range['end'])) return '—';

        $fmt   = fn ($t) => $t ? Carbon::parse($t)->format('g:i A') : null;
        $start = $fmt($range['start']);
        $end   = $fmt($range['end']);

        if ($start && $end) return $start . ' – ' . $end;
        return $start ?: $end;
    }

    /**
     * GET /admin/seating/plans/{id} — a session as the panel's plan viewer
     * heads it, with each room it seats (for the room's chart).
     */
    public function plan($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $plan = SeatingPlan::with('exam:id,exam_name')->where('organization_id', $user->organization_id)->find($id);
        if (!$plan) return $this->error('Seating plan not found.', 404);

        $assign = SeatAssignment::where('seating_plan_id', $plan->id)->get(['room_id', 'student_id']);
        $rooms  = SeatingRoom::whereIn('id', $assign->pluck('room_id')->unique())->orderBy('room_name')->get();

        return $this->success([
            'id'             => $plan->id,
            'name'           => $plan->name,
            'exam_name'      => $plan->exam->exam_name ?? null,
            'date'           => $plan->exam_date?->toDateString(),
            'session'        => $plan->session ?: 'Shift 1',
            'status'         => $plan->status,
            'notes'          => $plan->notes,
            'total_students' => (int) $plan->total_students,
            'total_seats'    => (int) $plan->total_seats,
            'conflicts'      => (int) $plan->conflict_count,
            'rooms'          => $rooms->map(fn ($r) => [
                'id'        => $r->id,
                'room_name' => $r->room_name,
                'building'  => $r->building,
                'filled'    => $assign->where('room_id', $r->id)->whereNotNull('student_id')->count(),
                'capacity'  => (int) $r->capacity,
            ])->values(),
        ], 'Seating plan fetched.');
    }

    /** POST /admin/seating/plans/{id}/publish — each student seated in it hears their room and seat. */
    public function publish($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $plan = SeatingPlan::where('organization_id', $user->organization_id)->find($id);
        if (!$plan) return $this->error('Seating plan not found.', 404);

        SeatingPlan::where('id', $plan->id)->where('organization_id', $user->organization_id)
            ->update(['status' => 'published']);
        app(\App\Services\StudentPushNotifier::class)->seatingPublished($plan->id);

        return $this->success(['id' => $plan->id, 'status' => 'published'], 'Plan published.');
    }

    /** DELETE /admin/seating/plans/{id} — its seat and invigilator assignments go with it. */
    public function destroyPlan($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $deleted = SeatingPlan::where('id', $id)->where('organization_id', $user->organization_id)->delete();
        if (!$deleted) return $this->error('Seating plan not found.', 404);

        return $this->success(null, 'Plan deleted.');
    }

    /**
     * GET /admin/seating/plans/{id}/list-pdf?room=&standard=&section=&subject=
     * The panel's seating list — the same narrowing the finder shows.
     */
    public function listPdf(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!SeatingPlan::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Seating plan not found.', 404);
        }

        return app(\App\Http\Controllers\Admin\SeatingListController::class)
            ->pdf($request, $user->organization_id, $id);
    }

    /** GET /admin/seating/plans/{id}/rooms/{roomId}/pdf — the panel's Room PDF. */
    public function roomPdf($id, $roomId)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!SeatingPlan::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Seating plan not found.', 404);
        }

        return app(\App\Http\Controllers\Admin\SeatingPlanPrintController::class)
            ->roomPdf($user->organization_id, $id, $roomId);
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  GENERATE PLAN — a copy of the panel's generatePlan()
    // ═══════════════════════════════════════════════════════════════════════

    /** Classes (standards) that have at least one dated paper for the exam. */
    private function datesheetStandardIds(int $orgId, int $examId): array
    {
        if (!$examId) return [];

        return ExamDatesheet::where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->whereHas('papers', fn ($q) => $q->whereNotNull('exam_date'))
            ->pluck('standard_id')->unique()->values()->all();
    }

    /**
     * GET /admin/seating/generate/options?exam_id=
     * What the Generate panel offers: every class (those with a datesheet for
     * the exam picked by default), the active rooms (all picked by default)
     * and a name from the exam.
     */
    public function generateOptions(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId  = $user->organization_id;
        $examId = (int) $request->input('exam_id');

        $exam = $examId ? Exam::where('organization_id', $orgId)->find($examId) : null;

        return $this->success([
            'datesheet_standard_ids' => $exam ? array_map('intval', $this->datesheetStandardIds($orgId, $exam->id)) : [],
            // Seated in this exam already — a class is not generated again.
            'generated_standard_ids' => $exam ? GeneratedSeating::standardIds((int) $orgId, (int) $exam->id) : [],
            'suggested_name'         => $exam ? $exam->exam_name . ' — Seating' : null,
            'standards'              => Standard::where('organization_id', $orgId)->where('is_active', true)
                ->inClassOrder()->get(['id', 'name'])->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'rooms'                  => SeatingRoom::where('organization_id', $orgId)->where('is_active', true)
                ->orderBy('room_name')->get(['id', 'room_name', 'building', 'capacity'])
                ->map(fn ($r) => ['id' => $r->id, 'room_name' => $r->room_name, 'building' => $r->building, 'capacity' => (int) $r->capacity])->values(),
        ], 'Generate options fetched.');
    }

    /** POST /admin/seating/generate {exam_id, name, standard_ids[], room_ids[]} */
    public function generate(Request $request, SeatingPlannerService $planner)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'exam_id'        => 'required|integer',
            'name'           => 'required|string|max:150',
            'standard_ids'   => 'required|array|min:1',
            'standard_ids.*' => 'integer',
            'room_ids'       => 'required|array|min:1',
            'room_ids.*'     => 'integer',
        ], [
            'exam_id.required'      => 'Select an exam.',
            'name.required'         => 'Enter a plan name.',
            'name.max'              => 'The plan name may be at most 150 characters.',
            'standard_ids.required' => 'Select at least one class.',
            'standard_ids.min'      => 'Select at least one class.',
            'room_ids.required'     => 'Select at least one room.',
            'room_ids.min'          => 'Select at least one room.',
        ])) return $err;

        $exam = Exam::where('organization_id', $orgId)->find((int) $request->exam_id);
        if (!$exam) return $this->error('Select an exam.', 422);

        $examId      = $exam->id;
        $baseName    = trim($request->name);
        $standardIds = array_map('intval', $request->standard_ids);

        // A class is seated for an exam once. The app ticks every datesheet
        // class by default, so the ones already generated are left out here,
        // and only when nothing else is left is it refused.
        $already = array_values(array_intersect($standardIds, GeneratedSeating::standardIds((int) $orgId, (int) $examId)));
        $alreadyNames = $already
            ? Standard::whereIn('id', $already)->inClassOrder()->pluck('name')->implode(', ')
            : '';
        $standardIds = array_values(array_diff($standardIds, $already));
        if (!$standardIds) {
            return $this->error($alreadyNames . ' already ' . (count($already) === 1 ? 'has' : 'have')
                . ' a seating plan for this exam — it is not generated again.', 422);
        }

        // 1. The datesheets (with papers) for this exam + selected classes.
        $datesheets = ExamDatesheet::with('papers.subject:id,name')
            ->where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->whereIn('standard_id', $standardIds)
            ->get();

        if ($datesheets->isEmpty()) {
            return $this->error('No datesheet found for the selected exam & classes. Create a datesheet first.', 422);
        }

        // 2. Papers grouped into exam sessions keyed by date + shift.
        $sessions = [];
        foreach ($datesheets as $ds) {
            foreach ($ds->papers as $p) {
                if (empty($p->exam_date)) continue;
                $dateStr = Carbon::parse($p->exam_date)->toDateString();
                $shift   = (int) ($p->shift ?: 1);
                $key     = $dateStr . '|' . $shift;

                $sessions[$key]['date']  = $dateStr;
                $sessions[$key]['shift'] = $shift;
                $sessions[$key]['entries'][] = [
                    'standard_id' => $ds->standard_id,
                    'section_id'  => $ds->section_id,
                    'subject'     => $p->subject->name ?? '',
                ];
            }
        }

        if (empty($sessions)) {
            return $this->error('The datesheet has no dated papers to seat.', 422);
        }

        // 3. The selected active rooms, in a fixed order.
        $baseRooms = SeatingRoom::with('seats')
            ->whereIn('id', $request->room_ids)
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('room_name')
            ->get();

        if ($baseRooms->isEmpty()) {
            return $this->error('No active rooms selected.', 422);
        }

        ksort($sessions); // chronological order

        $invigilators  = SeatingInvigilator::where('organization_id', $orgId)->get();
        $firstPlanId   = null;
        $createdPlans  = 0;
        $createdIds    = [];
        $seatedTotal   = 0;
        $skippedNoStud = 0;

        try {
            foreach ($sessions as $session) {
                // 3a. Students examined in this session.
                $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
                    ->where('organization_id', $orgId)
                    ->whereNotNull('user_id')
                    ->where(function ($q) use ($session) {
                        foreach ($session['entries'] as $e) {
                            $q->orWhere(function ($qq) use ($e) {
                                $qq->where('standard_id', $e['standard_id']);
                                if (!empty($e['section_id'])) {
                                    $qq->where('section_id', $e['section_id']);
                                }
                            });
                        }
                    })
                    ->get()
                    ->unique('user_id')
                    ->values();

                if ($students->isEmpty()) {
                    $skippedNoStud++;
                    continue;
                }

                $studentInput = $students->map(fn ($s) => [
                    'id'          => $s->user_id,
                    'name'        => $s->full_name,
                    'class_label' => ($s->standard->name ?? '?') . '-' . ($s->section->name ?? '-'),
                ])->toArray();

                // 3b. Rooms for this session, with an overflow hall if capacity is short.
                $rooms    = $baseRooms->map(fn ($r) => $r)->values();
                $capacity = (int) $rooms->sum('capacity');
                $overflow = $students->count() - $capacity;
                if ($overflow > 0) {
                    $rooms->push($this->overflowHall($orgId, $session, $overflow));
                }

                $result = $planner->plan($studentInput, $rooms);

                DB::transaction(function () use ($result, $rooms, $orgId, $examId, $baseName, $session, $invigilators, $planner, &$firstPlanId, &$createdPlans, &$seatedTotal, &$createdIds) {
                    $label   = Carbon::parse($session['date'])->format('d M Y');
                    $subject = collect($session['entries'])->pluck('subject')->filter()->unique()->implode(', ');
                    $name    = $baseName . ' — ' . $label . ($session['shift'] > 1 ? ' (Shift ' . $session['shift'] . ')' : '');

                    $plan = SeatingPlan::create([
                        'organization_id' => $orgId,
                        'exam_id'         => $examId,
                        'name'            => $name,
                        'exam_date'       => $session['date'],
                        'session'         => 'Shift ' . $session['shift'],
                        // A generated plan is published as it is made, as on the panel.
                        'status'          => 'published',
                        'generated_at'    => now(),
                        'total_students'  => $result['totals']['students'],
                        'total_seats'     => $result['totals']['seats'],
                        'conflict_count'  => $result['totals']['conflicts'],
                        'notes'           => $subject !== '' ? 'Subjects: ' . $subject : null,
                    ]);

                    $now  = now();
                    $rows = [];
                    foreach ($result['assignments'] as $a) {
                        if (!$a['seat_id']) continue;
                        $rows[] = [
                            'seating_plan_id' => $plan->id,
                            'seat_id'         => $a['seat_id'],
                            'seat_position'   => $a['seat_position'] ?? 1,
                            'room_id'         => $a['room_id'],
                            'student_id'      => $a['student_id'],
                            'class_label'     => $a['class_label'],
                            'has_conflict'    => $a['has_conflict'] ? 1 : 0,
                            'is_locked'       => 0,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ];
                    }
                    if ($rows) SeatAssignment::insert($rows);

                    // Invigilators for this session's date.
                    $invMap  = $planner->assignInvigilators($rooms, $invigilators, $session['date']);
                    $invRows = [];
                    foreach ($invMap as $roomId => $invigilatorIds) {
                        foreach ($invigilatorIds as $iid) {
                            $invRows[] = [
                                'seating_plan_id' => $plan->id,
                                'room_id'         => $roomId,
                                'invigilator_id'  => $iid,
                                'created_at'      => $now,
                                'updated_at'      => $now,
                            ];
                        }
                    }
                    if ($invRows) InvigilatorAssignment::insert($invRows);

                    $firstPlanId = $firstPlanId ?? $plan->id;
                    $createdIds[] = $plan->id;
                    $createdPlans++;
                    $seatedTotal += $result['totals']['students'];
                });
            }
        } catch (\Throwable $e) {
            report($e);
            return $this->error('Could not generate the seating plan: ' . $e->getMessage(), 500);
        }

        if ($createdPlans === 0) {
            return $this->error('No students found for the selected classes on any datesheet date.', 422);
        }

        // Each seated student hears it once for the exam (what Publish sent).
        app(\App\Services\StudentPushNotifier::class)->seatingGenerated($createdIds);

        $msg = "{$createdPlans} seating plan(s) generated · {$seatedTotal} student-seatings.";
        if ($skippedNoStud > 0) {
            $msg .= " {$skippedNoStud} date(s) skipped (no students).";
        }
        if ($already) {
            $msg .= " {$alreadyNames} left out (already generated).";
        }

        return $this->success([
            'created'       => $createdPlans,
            'seated'        => $seatedTotal,
            'skipped'       => $skippedNoStud,
            'first_plan_id' => $firstPlanId,
            'exam_id'       => $examId,
        ], $msg);
    }

    /** An overflow "Exam Hall" sized to the session's overflow, unique per session. */
    private function overflowHall(int $orgId, array $session, int $overflow): SeatingRoom
    {
        $cols       = 6;
        $rowsNeeded = max(1, (int) ceil($overflow / $cols));
        $hallName   = 'Exam Hall ' . $session['date'] . ' S' . $session['shift'];

        $hall = SeatingRoom::firstOrNew([
            'organization_id' => $orgId,
            'room_name'       => $hallName,
        ]);
        $hall->building      = 'Overflow';
        $hall->rows          = $rowsNeeded;
        $hall->columns       = $cols;
        $hall->seat_capacity = 1;
        $hall->capacity      = $rowsNeeded * $cols;
        $hall->is_active     = true;
        $hall->save();
        $this->regenerateSeats($hall);

        return $hall->load('seats');
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  ROOMS
    // ═══════════════════════════════════════════════════════════════════════

    private function shapeRoom(SeatingRoom $r): array
    {
        return [
            'id'            => $r->id,
            'room_name'     => $r->room_name,
            'building'      => $r->building,
            'rows'          => (int) $r->rows,
            'columns'       => (int) $r->columns,
            'seat_capacity' => $r->seatCapacity(),
            'capacity'      => (int) $r->capacity,
            'is_active'     => (bool) $r->is_active,
            'notes'         => $r->notes,
        ];
    }

    /** GET /admin/seating/rooms */
    public function rooms()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        // In the order they were added: the first room at the top.
        $rooms = SeatingRoom::where('organization_id', $user->organization_id)->orderBy('id')->get();

        return $this->success($rooms->map(fn ($r) => $this->shapeRoom($r))->values(), 'Rooms fetched.');
    }

    /** POST /admin/seating/rooms — Add Room */
    public function storeRoom(Request $request)
    {
        return $this->saveRoom($request, null);
    }

    /** POST /admin/seating/rooms/{id} — Edit Room */
    public function updateRoom(Request $request, $id)
    {
        return $this->saveRoom($request, $id);
    }

    private function saveRoom(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $room = null;
        if ($id !== null) {
            $room = SeatingRoom::where('organization_id', $orgId)->find($id);
            if (!$room) return $this->error('Room not found.', 404);
        }

        if ($err = $this->validateWith($request, [
            'room_name'     => 'required|string|max:100',
            'building'      => 'nullable|string|max:100',
            'rows'          => 'required|integer|min:1|max:50',
            'columns'       => 'required|integer|min:1|max:50',
            'seat_capacity' => 'required|integer|min:1|max:10',
            'is_active'     => 'nullable|boolean',
            'notes'         => 'nullable|string',
        ], [
            'room_name.required'     => 'Enter the room name.',
            'room_name.max'          => 'The room name may be at most 100 characters.',
            'building.max'           => 'The building may be at most 100 characters.',
            'rows.*'                 => 'Rows must be between 1 and 50.',
            'columns.*'              => 'Columns must be between 1 and 50.',
            'seat_capacity.*'        => 'Seats per desk must be between 1 and 10.',
        ])) return $err;

        $rows    = (int) $request->rows;
        $cols    = (int) $request->columns;
        $perSeat = max(1, (int) $request->seat_capacity);

        $data = [
            'organization_id' => $orgId,
            'room_name'       => $request->room_name,
            'building'        => $request->building,
            'rows'            => $rows,
            'columns'         => $cols,
            'seat_capacity'   => $perSeat,
            // A desk can take more than one candidate: rows × columns × seats-per-desk.
            'capacity'        => $rows * $cols * $perSeat,
            'is_active'       => $request->has('is_active') ? $request->boolean('is_active') : true,
            'notes'           => $request->notes,
        ];

        DB::transaction(function () use (&$room, $data) {
            if ($room) {
                $room->update($data);
            } else {
                $room = SeatingRoom::create($data);
            }
            $this->regenerateSeats($room);
        });

        return $this->success($this->shapeRoom($room->fresh()), $id !== null ? 'Room updated.' : 'Room added.');
    }

    private function regenerateSeats(SeatingRoom $room): void
    {
        SeatingSeat::where('room_id', $room->id)->delete();
        $rows = [];
        $now  = now();
        for ($r = 1; $r <= $room->rows; $r++) {
            for ($c = 1; $c <= $room->columns; $c++) {
                $rows[] = [
                    'room_id'     => $room->id,
                    'row_no'      => $r,
                    'col_no'      => $c,
                    'seat_number' => SeatLabel::seat($r, $c),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }
        SeatingSeat::insert($rows);
    }

    /** DELETE /admin/seating/rooms/{id} — its seats go; existing plans keep their snapshot. */
    public function destroyRoom($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $deleted = SeatingRoom::where('id', $id)->where('organization_id', $user->organization_id)->delete();
        if (!$deleted) return $this->error('Room not found.', 404);

        return $this->success(null, 'Room removed.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  DATESHEET
    // ═══════════════════════════════════════════════════════════════════════

    private function timeOf($t): ?string
    {
        return $t ? substr((string) $t, 0, 5) : null;
    }

    /**
     * GET /admin/seating/datesheet?exam_id=&standard_id=&section_id=&subject_id=
     *
     * The sheet the filters point at — the section's own, else the class-wide
     * one — its papers by date (one subject's alone when asked), and the
     * subjects to narrow by.
     */
    public function datesheet(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $examId     = (int) $request->input('exam_id');
        $standardId = (int) $request->input('standard_id');
        $sectionId  = (int) $request->input('section_id');
        $subjectId  = (int) $request->input('subject_id');

        $sheet = ($examId && $standardId && $sectionId)
            ? ExamDatesheet::with(['exam:id,exam_name,academic_year', 'standard:id,name', 'section:id,name', 'papers.subject:id,name'])
                ->where('organization_id', $orgId)
                ->where('exam_id', $examId)
                ->where('standard_id', $standardId)
                ->where(fn ($q) => $q->where('section_id', $sectionId)->orWhereNull('section_id'))
                ->orderByRaw('section_id IS NULL')  // the section's own sheet wins
                ->first()
            : null;

        $total = ExamDatesheet::where('organization_id', $orgId)->count();

        if (!$sheet) {
            return $this->success(['datesheet' => null, 'subjects' => [], 'total' => $total], 'Datesheet fetched.');
        }

        $papers = $sheet->papers
            ->sortBy(fn ($p) => ($p->exam_date ? Carbon::parse($p->exam_date)->toDateString() : '9999-12-31') . ' ' . ($p->start_time ?? ''))
            ->values();

        $subjects = $papers->map(fn ($p) => ['id' => $p->subject_id, 'name' => $p->subject->name ?? 'Subject'])
            ->unique('id')->values();

        if ($subjectId) {
            $papers = $papers->where('subject_id', $subjectId)->values();
        }

        $sectionName = $sheet->section->name ?? Section::where('id', $sectionId)->value('name');

        return $this->success([
            'datesheet' => [
                'id'            => $sheet->id,
                'exam_id'       => $sheet->exam_id,
                'exam_name'     => $sheet->exam->exam_name ?? null,
                'standard_id'   => $sheet->standard_id,
                'standard_name' => $sheet->standard->name ?? null,
                'section_id'    => $sheet->section_id,
                'section_name'  => $sectionName,
                'class_wide'    => !$sheet->section_id,
                'papers'        => $papers->map(fn ($p) => [
                    'id'           => $p->id,
                    'subject_id'   => $p->subject_id,
                    'subject_name' => $p->subject->name ?? '—',
                    'exam_date'    => $p->exam_date ? Carbon::parse($p->exam_date)->toDateString() : null,
                    'start_time'   => $this->timeOf($p->start_time),
                    'end_time'     => $this->timeOf($p->end_time),
                    'shift'        => (int) ($p->shift ?: 1),
                ])->values(),
            ],
            'subjects' => $subjects,
            'total'    => $total,
        ], 'Datesheet fetched.');
    }

    /** The class's (or section's) active subjects, as the panel's form loads them. */
    private function formSubjects(int $orgId, int $standardId, int $sectionId)
    {
        if ($sectionId) {
            return Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $orgId)->where('subjects.is_active', true)
                ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
        }

        return Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
            ->where('standard_subjects.standard_id', $standardId)
            ->where('subjects.organization_id', $orgId)->where('subjects.is_active', true)
            ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
    }

    /**
     * GET /admin/seating/datesheet/form?datesheet_id= | ?standard_id=&section_id=
     *
     * The form's rows: a new sheet starts from the class's (or section's)
     * subjects, blank; an edited one from them too, filled in with its papers
     * (a subject no longer mapped keeps its paper).
     */
    public function datesheetForm(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $sheet = null;
        if ($request->filled('datesheet_id')) {
            $sheet = ExamDatesheet::with('papers.subject:id,name')->where('organization_id', $orgId)->find((int) $request->datesheet_id);
            if (!$sheet) return $this->error('That datesheet is gone.', 404);
        }

        $standardId = $sheet ? (int) $sheet->standard_id : (int) $request->input('standard_id');
        $sectionId  = $sheet ? (int) ($sheet->section_id ?? 0) : (int) $request->input('section_id');

        $rows = [];
        if ($standardId && Standard::where('organization_id', $orgId)->whereKey($standardId)->exists()) {
            foreach ($this->formSubjects($orgId, $standardId, $sectionId) as $s) {
                $rows[$s->id] = [
                    'subject_id' => $s->id, 'name' => $s->name,
                    'exam_date' => null, 'start_time' => null, 'end_time' => null, 'shift' => 1,
                ];
            }
        }

        if ($sheet) {
            foreach ($sheet->papers as $paper) {
                $row = $rows[$paper->subject_id] ?? ['subject_id' => $paper->subject_id, 'name' => $paper->subject->name ?? 'Subject'];
                $rows[$paper->subject_id] = array_merge($row, [
                    'exam_date'  => $paper->exam_date ? Carbon::parse($paper->exam_date)->toDateString() : null,
                    'start_time' => $this->timeOf($paper->start_time),
                    'end_time'   => $this->timeOf($paper->end_time),
                    'shift'      => (int) ($paper->shift ?: 1),
                ]);
            }
        }

        return $this->success([
            'datesheet_id' => $sheet?->id,
            'exam_id'      => $sheet?->exam_id,
            'standard_id'  => $standardId ?: null,
            'section_id'   => $sectionId ?: null,
            'papers'       => array_values($rows),
        ], 'Datesheet form fetched.');
    }

    /**
     * POST /admin/seating/datesheet
     *   {datesheet_id?, exam_id, standard_id, section_id?, papers: [{subject_id, name?, exam_date, start_time, end_time, shift}]}
     *
     * The panel's save: at least one dated paper, no clash, the sheet's papers
     * replaced wholesale; the class's teachers hear about their papers.
     */
    public function saveDatesheet(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'datesheet_id'         => 'nullable|integer',
            'exam_id'              => 'required|integer',
            'standard_id'          => 'required|integer',
            'section_id'           => 'nullable|integer',
            'papers'               => 'nullable|array',
            'papers.*.subject_id'  => 'required|integer',
            'papers.*.exam_date'   => 'nullable|date_format:Y-m-d',
            'papers.*.start_time'  => 'nullable|date_format:H:i',
            'papers.*.end_time'    => 'nullable|date_format:H:i',
            'papers.*.shift'       => 'nullable|integer|in:1,2',
        ], [
            'exam_id.required'     => 'Select an exam.',
            'standard_id.required' => 'Select a class.',
            'papers.*.exam_date.date_format'  => 'A paper has a date that is not valid.',
            'papers.*.start_time.date_format' => 'A paper has a start time that is not valid.',
            'papers.*.end_time.date_format'   => 'A paper has an end time that is not valid.',
        ])) return $err;

        $examId     = (int) $request->exam_id;
        $standardId = (int) $request->standard_id;
        $sectionId  = $request->filled('section_id') ? (int) $request->section_id : null;
        $editId     = $request->filled('datesheet_id') ? (int) $request->datesheet_id : null;

        if (!Exam::where('organization_id', $orgId)->whereKey($examId)->exists()) {
            return $this->error('Select an exam.', 422);
        }
        if (!Standard::where('organization_id', $orgId)->whereKey($standardId)->exists()) {
            return $this->error('Select a class.', 422);
        }
        if ($sectionId && !Section::where('standard_id', $standardId)->whereKey($sectionId)->exists()) {
            return $this->error('Select a section of this class.', 422);
        }
        if ($editId && !ExamDatesheet::where('organization_id', $orgId)->whereKey($editId)->exists()) {
            return $this->error('That datesheet is gone.', 404);
        }

        // Subject names for the clash messages.
        $names  = Subject::whereIn('id', collect($request->input('papers', []))->pluck('subject_id'))->pluck('name', 'id');
        $papers = [];
        foreach ((array) $request->input('papers', []) as $p) {
            $sid = (int) ($p['subject_id'] ?? 0);
            if (!$sid) continue;
            $papers[$sid] = [
                'name'       => $names[$sid] ?? ($p['name'] ?? 'Subject'),
                'exam_date'  => $p['exam_date'] ?? '',
                'start_time' => $p['start_time'] ?? '',
                'end_time'   => $p['end_time'] ?? '',
                'shift'      => (int) ($p['shift'] ?? 1) ?: 1,
            ];
        }

        $filled = collect($papers)->filter(fn ($p) => !empty($p['exam_date']));
        if ($filled->isEmpty()) {
            return $this->error('Set a date for at least one subject.', 422);
        }

        $clashes = $this->datesheetClashes($orgId, $papers, $editId, $examId, $standardId, $sectionId);
        if ($clashes) {
            return $this->error(implode(' ', array_slice($clashes, 0, 3)), 422, ['papers' => $clashes]);
        }

        // The papers as they were, so a teacher hears only when theirs change.
        $push   = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->datesheetSnapshot($orgId, $editId, $examId, $standardId, $sectionId);

        $sheetId = null;
        DB::transaction(function () use ($orgId, $editId, $examId, $standardId, $sectionId, $papers, &$sheetId) {
            $ds = $editId ? ExamDatesheet::where('organization_id', $orgId)->findOrFail($editId) : null;

            if ($ds) {
                $ds->update([
                    'exam_id'     => $examId,
                    'standard_id' => $standardId,
                    'section_id'  => $sectionId ?: null,
                ]);
            }

            $ds = $ds ?: ExamDatesheet::updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'exam_id'         => $examId,
                    'standard_id'     => $standardId,
                    'section_id'      => $sectionId ?: null,
                ],
                []
            );

            ExamDatesheetPaper::where('exam_datesheet_id', $ds->id)->delete();

            foreach ($papers as $subjectId => $p) {
                if (empty($p['exam_date'])) continue;
                ExamDatesheetPaper::create([
                    'exam_datesheet_id' => $ds->id,
                    'subject_id'        => $subjectId,
                    'exam_date'         => $p['exam_date'],
                    'start_time'        => $p['start_time'] ?: null,
                    'end_time'          => $p['end_time'] ?: null,
                    'shift'             => (int) ($p['shift'] ?? 1),
                ]);
            }

            $sheetId = $ds->id;
        });
        $push->datesheetSaved($before, $examId, $standardId, $sectionId);

        return $this->success([
            'datesheet_id' => $sheetId,
            'exam_id'      => $examId,
            'standard_id'  => $standardId,
            'section_id'   => $sectionId,
        ], $editId ? 'Datesheet updated.' : 'Datesheet saved.');
    }

    /**
     * Two papers clash when the same class sits both at once: the same date
     * with overlapping times, or — when a paper carries no times — the same
     * date and shift. Checked inside the form and against every datesheet the
     * class already has, including the class-wide one a section inherits.
     */
    private function datesheetClashes(int $orgId, array $papers, ?int $editId, int $examId, int $standardId, ?int $sectionId): array
    {
        $rows = [];
        foreach ($papers as $p) {
            if (empty($p['exam_date'])) continue;
            $rows[] = [
                'subject' => $p['name'] ?? 'Subject',
                'date'    => $p['exam_date'],
                'start'   => $p['start_time'] ?: null,
                'end'     => $p['end_time'] ?: null,
                'shift'   => (int) ($p['shift'] ?? 1),
            ];
        }

        $clashes = [];

        // Inside the form
        for ($i = 0; $i < count($rows); $i++) {
            for ($j = $i + 1; $j < count($rows); $j++) {
                if ($this->papersOverlap($rows[$i], $rows[$j])) {
                    $clashes[] = $rows[$i]['subject'] . ' and ' . $rows[$j]['subject']
                        . ' are both on ' . $this->prettyDate($rows[$i]['date']) . ' at the same time.';
                }
            }
        }

        // The sheet this save writes to is replaced wholesale.
        $targetId = $editId ?: ExamDatesheet::where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->where('standard_id', $standardId)
            ->where(fn ($q) => $sectionId ? $q->where('section_id', $sectionId) : $q->whereNull('section_id'))
            ->value('id');

        // Against what the class already has (any exam).
        $existing = ExamDatesheetPaper::query()
            ->join('exam_datesheets as d', 'd.id', '=', 'exam_datesheet_papers.exam_datesheet_id')
            ->join('subjects as s', 's.id', '=', 'exam_datesheet_papers.subject_id')
            ->leftJoin('exams as e', 'e.id', '=', 'd.exam_id')
            ->where('d.organization_id', $orgId)
            ->where('d.standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('d.section_id')->orWhere('d.section_id', $sectionId)))
            ->when($targetId, fn ($q) => $q->where('d.id', '!=', $targetId))
            ->get([
                'exam_datesheet_papers.exam_date', 'exam_datesheet_papers.start_time',
                'exam_datesheet_papers.end_time', 'exam_datesheet_papers.shift',
                's.name as subject_name', 'e.exam_name as exam_name',
            ]);

        foreach ($rows as $row) {
            foreach ($existing as $old) {
                $other = [
                    'date'  => Carbon::parse($old->exam_date)->toDateString(),
                    'start' => $old->start_time ? substr($old->start_time, 0, 5) : null,
                    'end'   => $old->end_time ? substr($old->end_time, 0, 5) : null,
                    'shift' => (int) ($old->shift ?: 1),
                ];
                if ($this->papersOverlap($row, $other)) {
                    $clashes[] = $row['subject'] . ' on ' . $this->prettyDate($row['date'])
                        . ' runs into ' . $old->subject_name
                        . ($old->exam_name ? ' (' . $old->exam_name . ')' : '')
                        . ', already set at that time for this class.';
                }
            }
        }

        return array_values(array_unique($clashes));
    }

    /** Same day, and either overlapping clock times or the same shift. */
    private function papersOverlap(array $a, array $b): bool
    {
        if ($a['date'] !== $b['date']) return false;

        if ($a['start'] && $a['end'] && $b['start'] && $b['end']) {
            return $a['start'] < $b['end'] && $b['start'] < $a['end'];
        }

        return (int) $a['shift'] === (int) $b['shift'];
    }

    private function prettyDate(string $date): string
    {
        try {
            return Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return $date;
        }
    }

    /** DELETE /admin/seating/datesheet/{id} — every paper on it goes with it. */
    public function destroyDatesheet($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $ds = ExamDatesheet::where('id', $id)->where('organization_id', $user->organization_id)->first();
        if (!$ds) return $this->error('Datesheet not found.', 404);

        ExamDatesheetPaper::where('exam_datesheet_id', $ds->id)->delete();
        $ds->delete();

        return $this->success(null, 'Datesheet deleted.');
    }

    /**
     * GET /admin/seating/datesheet/{id}/pdf?subject=&section=
     * The panel's Print page as a PDF — its print styles, A4 landscape.
     */
    public function datesheetPdf(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!ExamDatesheet::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Datesheet not found.', 404);
        }

        $view = app(\App\Http\Controllers\Admin\DatesheetPrintController::class)
            ->print($request, $user->organization_id, $id);
        $data = $view->getData();

        $pdf = Pdf::loadHTML($view->render())
            ->setPaper('a4', 'landscape')
            ->setOption('defaultMediaType', 'print')
            ->setOption('defaultFont', 'DejaVu Sans');

        $who  = trim(($data['datesheet']->standard->name ?? '') . ' ' . ($data['sectionName'] ?? ''));
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', 'datesheet ' . $who) ?: 'datesheet';

        return $pdf->download($safe . '.pdf');
    }
}
