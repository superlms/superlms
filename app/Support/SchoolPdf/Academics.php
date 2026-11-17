<?php

namespace App\Support\SchoolPdf;

use App\Support\AcademicYear;
use Illuminate\Support\Facades\DB;

/**
 * Attendance, Homework, Time Table, Arrangement, Syllabus & Content,
 * Assignments, Books, Exams, Admit Cards, Seating Plan, Performance,
 * Report Cards and TC & Certificates.
 */
class Academics
{
    private const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /** Attendance this session: each student's days, class by class, then teachers and staff. */
    public static function attendance(Ctx $c): array
    {
        $from = AcademicYear::start()->toDateString();
        $sum  = fn (string $col, int $v) => "SUM(CASE WHEN {$col} = {$v} THEN 1 ELSE 0 END)";

        $st = DB::table('student_attendances')->where('organization_id', $c->org)->whereDate('attendance_date', '>=', $from)
            ->selectRaw('student_detail_id, COUNT(*) as total, ' . $sum('status', 1) . ' as p, ' . $sum('status', 0) . ' as a, '
                . $sum('status', 2) . ' as h, SUM(CASE WHEN status IN (3, 4) THEN 1 ELSE 0 END) as hol')
            ->groupBy('student_detail_id')->get()->keyBy('student_detail_id');

        $ids = $c->byClass(array_keys($c->students()), fn ($id) => $c->student($id)['standard_id'],
            fn ($id) => $c->student($id)['section_id'], fn ($id) => $c->student($id)['name']);

        $blocks = [];
        $groups = [];
        foreach ($ids as $id) {
            $groups[$c->student($id)['class']][] = $id;
        }
        $allP = $allT = 0;
        foreach ($groups as $label => $list) {
            $rows = [];
            $gp = $gt = 0;
            foreach ($list as $id) {
                $s = $c->student($id);
                $a = $st[$id] ?? null;
                $marked = $a ? (int) $a->p + (int) $a->a + (int) $a->h : 0;
                $gp += $a ? (int) $a->p + 0.5 * (int) $a->h : 0;
                $gt += $marked;
                $rows[] = [Ctx::dash($s['roll']), $s['name'], Ctx::dash($s['adm']), (int) ($a->p ?? 0), (int) ($a->a ?? 0), (int) ($a->h ?? 0),
                    (int) ($a->hol ?? 0), $marked, $a ? Ctx::pct((int) $a->p + 0.5 * (int) $a->h, $marked) : '—'];
            }
            $allP += $gp;
            $allT += $gt;
            $blocks[] = ['type' => 'heading', 'text' => $label, 'meta' => count($list) . ' students · ' . Ctx::pct($gp, $gt) . ' present'];
            $blocks[] = ['type' => 'table', 'columns' => [['Roll', 'w' => 7], ['Student', 'w' => 27], ['Adm No', 'w' => 10],
                ['Present', 'w' => 8, 'align' => 'right'], ['Absent', 'w' => 8, 'align' => 'right'], ['Half day', 'w' => 8, 'align' => 'right'],
                ['Holiday', 'w' => 8, 'align' => 'right'], ['School days', 'w' => 12, 'align' => 'right'], ['%', 'w' => 8, 'align' => 'right']], 'rows' => $rows];
        }

        // Teachers
        $ta = DB::table('teacher_attendances')->where('organization_id', $c->org)->whereDate('attendance_date', '>=', $from)
            ->selectRaw('teacher_detail_id, ' . $sum('status', 1) . ' as p, ' . $sum('status', 0) . ' as a, ' . $sum('status', 2) . ' as h, '
                . 'SUM(CASE WHEN status IN (3, 4) THEN 1 ELSE 0 END) as hol')
            ->groupBy('teacher_detail_id')->get()->keyBy('teacher_detail_id');
        $tRows = [];
        foreach ($c->teachers() as $id => $name) {
            $a = $ta[$id] ?? null;
            $marked = $a ? (int) $a->p + (int) $a->a + (int) $a->h : 0;
            $tRows[] = [$name ?: '—', (int) ($a->p ?? 0), (int) ($a->a ?? 0), (int) ($a->h ?? 0), (int) ($a->hol ?? 0), $marked,
                $a ? Ctx::pct((int) $a->p + 0.5 * (int) $a->h, $marked) : '—'];
        }
        usort($tRows, fn ($x, $y) => strcasecmp($x[0], $y[0]));
        if ($tRows) {
            $blocks[] = ['type' => 'heading', 'text' => 'Teachers', 'meta' => count($tRows) . ' teachers', 'break' => true];
            $blocks[] = ['type' => 'table', 'columns' => [['Teacher', 'w' => 36], ['Present', 'w' => 10, 'align' => 'right'], ['Absent', 'w' => 10, 'align' => 'right'],
                ['Half day', 'w' => 10, 'align' => 'right'], ['Holiday', 'w' => 10, 'align' => 'right'], ['Days marked', 'w' => 12, 'align' => 'right'],
                ['%', 'w' => 12, 'align' => 'right']], 'rows' => $tRows];
        }

        // Staff
        $sa = DB::table('admin_attendances')->where('organization_id', $c->org)->whereDate('date', '>=', $from)
            ->selectRaw("admin_employee_id, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as p, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as a,"
                . " SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as h, SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as l,"
                . " SUM(CASE WHEN status = 'holiday' THEN 1 ELSE 0 END) as hol")
            ->groupBy('admin_employee_id')->get();
        $sRows = [];
        foreach ($sa as $a) {
            $marked = (int) $a->p + (int) $a->a + (int) $a->h + (int) $a->l;
            $sRows[] = [$c->employees()[$a->admin_employee_id] ?? '—', (int) $a->p, (int) $a->a, (int) $a->h, (int) $a->l, (int) $a->hol,
                Ctx::pct((int) $a->p + 0.5 * (int) $a->h, $marked)];
        }
        usort($sRows, fn ($x, $y) => strcasecmp($x[0], $y[0]));
        if ($sRows) {
            $blocks[] = ['type' => 'heading', 'text' => 'Staff', 'meta' => count($sRows) . ' people'];
            $blocks[] = ['type' => 'table', 'columns' => [['Name', 'w' => 34], ['Present', 'w' => 10, 'align' => 'right'], ['Absent', 'w' => 10, 'align' => 'right'],
                ['Half day', 'w' => 10, 'align' => 'right'], ['Leave', 'w' => 10, 'align' => 'right'], ['Holiday', 'w' => 12, 'align' => 'right'],
                ['%', 'w' => 14, 'align' => 'right']], 'rows' => $sRows];
        }

        return [
            'title'    => 'Attendance',
            'subtitle' => 'Days present, absent and on half day this session (' . AcademicYear::label() . '), from ' . Ctx::date($from),
            'count'    => (int) $st->sum('total'),
            'summary'  => [['Students', number_format(count($ids))], ['Present overall', Ctx::pct($allP, $allT)], ['Teachers', number_format(count($tRows))], ['Staff', number_format(count($sRows))]],
            'blocks'   => $blocks,
        ];
    }

    /** Homework given, newest first, with how many students marked it done. */
    public static function homework(Ctx $c): array
    {
        $rows = DB::table('home_works')->where('organization_id', $c->org)->orderByDesc('created_at')->get();
        $done = DB::table('home_work_completions')->whereIn('home_work_id', $rows->pluck('id'))
            ->selectRaw('home_work_id, COUNT(*) as c')->groupBy('home_work_id')->pluck('c', 'home_work_id');

        return self::table($rows->map(fn ($h) => [
            Ctx::date($h->created_at), $c->classLabel($h->standard_id, $h->section_id), $c->subject($h->subject_id), Ctx::text($h->title),
            Ctx::text($h->description), $c->user($h->user_id), $h->file ? 'Yes' : '—', (int) ($done[$h->id] ?? 0),
        ])->all(), [['Given on', 'w' => 9], ['Class', 'w' => 10], ['Subject', 'w' => 10], ['Title', 'w' => 16], ['Details', 'w' => 30],
            ['Given by', 'w' => 11], ['File', 'w' => 5], ['Done by', 'w' => 9, 'align' => 'right']],
            'Homework', 'Every homework given, newest first, with how many students finished it', 'landscape');
    }

    /** The weekly time table, class by class. */
    public static function timetable(Ctx $c): array
    {
        $rows = DB::table('teacher_time_tables')->where('organization_id', $c->org)->where('is_active', true)
            ->orderBy('day_of_week')->orderBy('start_time')->get();
        $groups = [];
        foreach ($rows as $r) {
            $groups[(int) $r->standard_id . '-' . (int) $r->section_id][] = $r;
        }
        uksort($groups, function ($a, $b) use ($c) {
            $rank = $c->classRank();
            [$sa, $xa] = explode('-', $a);
            [$sb, $xb] = explode('-', $b);

            return [($rank[$sa] ?? PHP_INT_MAX), $c->sections()[$xa] ?? ''] <=> [($rank[$sb] ?? PHP_INT_MAX), $c->sections()[$xb] ?? ''];
        });

        $blocks = [];
        foreach ($groups as $key => $list) {
            [$std, $sec] = explode('-', $key);
            $blocks[] = ['type' => 'heading', 'text' => $c->classLabel((int) $std, (int) $sec), 'meta' => count($list) . ' periods a week'];
            $blocks[] = ['type' => 'table', 'columns' => [['Day', 'w' => 16], ['Time', 'w' => 24], ['Subject', 'w' => 30], ['Teacher', 'w' => 30]],
                'rows' => array_map(fn ($r) => [self::DAYS[(int) $r->day_of_week] ?? Ctx::dash($r->day_of_week),
                    Ctx::time($r->start_time) . ' – ' . Ctx::time($r->end_time), $c->subject($r->subject_id), $c->teachers()[$r->teacher_detail_id] ?? '—'], $list)];
        }

        $periods = DB::table('timetable_periods')->where('organization_id', $c->org)->orderBy('type')->orderBy('period_no')->get();
        if ($periods->isNotEmpty()) {
            array_unshift($blocks, ['type' => 'heading', 'text' => 'School periods', 'meta' => $periods->count() . ' periods'],
                ['type' => 'table', 'columns' => [['Period', 'w' => 20], ['Type', 'w' => 20], ['Starts', 'w' => 30], ['Ends', 'w' => 30]],
                    'rows' => $periods->map(fn ($p) => [(string) $p->period_no, Ctx::headline($p->type), Ctx::time($p->start_time), Ctx::time($p->end_time)])->all()]);
        }

        return ['title' => 'Time Table', 'subtitle' => 'The weekly periods of every class: day, time, subject and teacher', 'count' => $rows->count(),
            'summary' => [['Classes', number_format(count($groups))], ['Periods a week', number_format($rows->count())]], 'blocks' => $blocks];
    }

    /** Substitutions: who took whose period, and why. */
    public static function arrangement(Ctx $c): array
    {
        $rows = DB::table('teacher_arrangements as a')->leftJoin('teacher_time_tables as t', 't.id', '=', 'a.teacher_time_table_id')
            ->where('a.organization_id', $c->org)->orderByDesc('a.date')
            ->get(['a.*', 't.standard_id', 't.section_id', 't.subject_id', 't.start_time', 't.end_time']);

        return self::table($rows->map(fn ($r) => [
            Ctx::date($r->date), $r->start_time ? Ctx::time($r->start_time) . ' – ' . Ctx::time($r->end_time) : '—',
            $c->classLabel($r->standard_id, $r->section_id), $c->subject($r->subject_id),
            $c->teachers()[$r->original_teacher_id] ?? '—', $c->teachers()[$r->substitute_teacher_id] ?? '—', Ctx::dash($r->reason), $c->user($r->arranged_by),
        ])->all(), [['Date', 'w' => 10], ['Period', 'w' => 14], ['Class', 'w' => 10], ['Subject', 'w' => 11], ['Teacher away', 'w' => 14],
            ['Substitute', 'w' => 14], ['Reason', 'w' => 17], ['Arranged by', 'w' => 10]],
            'Arrangement', 'Every substitution — whose period, who took it and why', 'landscape');
    }

    /** Syllabus & content: chapters by class and subject, with their topics. */
    public static function syllabus(Ctx $c): array
    {
        $chapters = DB::table('chapters')->where('organization_id', $c->org)->orderBy('order')->orderBy('id')->get();
        $topics   = DB::table('topics')->whereIn('chapter_id', $chapters->pluck('id'))->orderBy('order')->get()->groupBy('chapter_id');
        $plans    = DB::table('student_syllabi')->where('organization_id', $c->org)->get();

        $rows = $chapters->map(fn ($ch) => [
            'std' => $ch->standard_id, 'sec' => $ch->section_id,
            'cells' => [$c->classLabel($ch->standard_id, $ch->section_id), $c->subject($ch->subject_id), Ctx::dash($ch->name),
                Ctx::headline($ch->content_type ?? null),
                ($topics[$ch->id] ?? collect())->pluck('topic_name')->filter()->implode(', ') ?: '—',
                ($ch->is_published ?? 1) ? 'Yes' : 'No'],
        ])->all();
        $rows = array_map(fn ($r) => $r['cells'], $c->byClass($rows, fn ($r) => $r['std'], fn ($r) => $r['sec'], fn ($r) => $r['cells'][1]));

        $blocks = [['type' => 'table', 'columns' => [['Class', 'w' => 11], ['Subject', 'w' => 13], ['Chapter', 'w' => 22], ['Type', 'w' => 8],
            ['Topics', 'w' => 38], ['Published', 'w' => 8]], 'rows' => $rows]];
        if ($plans->isNotEmpty()) {
            $blocks[] = ['type' => 'heading', 'text' => 'Syllabus plans', 'meta' => $plans->count() . ' entries'];
            $blocks[] = ['type' => 'table', 'columns' => [['Class', 'w' => 14], ['Subject', 'w' => 16], ['Name', 'w' => 22], ['Details', 'w' => 48]],
                'rows' => $plans->map(fn ($p) => [$c->classLabel($p->standard_id, $p->section_id), $c->subject($p->subject_id), Ctx::dash($p->name),
                    Ctx::text($p->description)])->all()];
        }

        return ['title' => 'Syllabus & Content', 'subtitle' => 'Chapters and topics for every class and subject', 'orientation' => 'landscape',
            'count' => $chapters->count() + $plans->count(),
            'summary' => [['Chapters', number_format($chapters->count())], ['Topics', number_format($topics->flatten()->count())]], 'blocks' => $blocks];
    }

    /** Assignments with how many submitted and how many were checked. */
    public static function assignments(Ctx $c): array
    {
        $rows = DB::table('assignments')->where('organization_id', $c->org)->orderByDesc('start_date')->get();
        $subs = DB::table('assignment_submissions')->whereIn('assignment_id', $rows->pluck('id'))
            ->selectRaw('assignment_id, COUNT(*) as n, SUM(CASE WHEN reviewed_at IS NOT NULL THEN 1 ELSE 0 END) as checked')
            ->groupBy('assignment_id')->get()->keyBy('assignment_id');

        return self::table($rows->map(fn ($a) => [
            $c->classLabel($a->standard_id, $a->section_id), $c->subject($a->subject_id), Ctx::dash($a->title), Ctx::text($a->description),
            Ctx::headline($a->type), Ctx::date($a->start_date) . ' – ' . Ctx::date($a->end_date), Ctx::dash($a->total_marks),
            (int) ($subs[$a->id]->n ?? 0) . ' / ' . (int) ($subs[$a->id]->checked ?? 0), $c->user($a->user_id), $a->is_active ? 'Open' : 'Closed',
        ])->all(), [['Class', 'w' => 9], ['Subject', 'w' => 9], ['Title', 'w' => 14], ['Details', 'w' => 22], ['Type', 'w' => 6],
            ['Dates', 'w' => 14], ['Marks', 'w' => 5, 'align' => 'right'], ['Submitted / checked', 'w' => 8, 'align' => 'right'], ['By', 'w' => 8], ['Status', 'w' => 5]],
            'Assignments', 'Every assignment with its dates, marks and submissions', 'landscape');
    }

    /** Books for each class and subject, and the library. */
    public static function books(Ctx $c): array
    {
        $books = DB::table('books')->where('organization_id', $c->org)->get();
        $rows  = $c->byClass($books->all(), fn ($b) => $b->standard_id, fn ($b) => $b->section_id, fn ($b) => $b->title);
        $blocks = [['type' => 'table', 'columns' => [['Class', 'w' => 20], ['Subject', 'w' => 22], ['Title', 'w' => 40], ['PDF', 'w' => 8], ['Active', 'w' => 10]],
            'rows' => array_map(fn ($b) => [$c->classLabel($b->standard_id, $b->section_id), $c->subject($b->subject_id), Ctx::dash($b->title),
                $b->pdf_file ? 'Yes' : '—', $b->is_active ? 'Yes' : 'No'], $rows)]];

        $library = Ctx::has('libraries') ? DB::table('libraries')->where('organization_id', $c->org)->orderBy('title')->get() : collect();
        if ($library->isNotEmpty()) {
            $blocks[] = ['type' => 'heading', 'text' => 'Library', 'meta' => $library->count() . ' books'];
            $blocks[] = ['type' => 'table', 'columns' => [['Title', 'w' => 22], ['Author', 'w' => 14], ['Publisher', 'w' => 12], ['Year', 'w' => 6],
                ['ISBN', 'w' => 12], ['Category', 'w' => 10], ['Language', 'w' => 8], ['Pages', 'w' => 6, 'align' => 'right'], ['Status', 'w' => 10]],
                'rows' => $library->map(fn ($l) => [Ctx::dash($l->title), Ctx::dash($l->author), Ctx::dash($l->publisher), Ctx::dash($l->publication_year),
                    Ctx::dash($l->isbn), Ctx::dash($l->category), Ctx::dash($l->language), Ctx::dash($l->pages), Ctx::headline($l->availability)])->all()];
        }

        return ['title' => 'Books', 'subtitle' => 'Books for every class and subject, and the library', 'count' => $books->count() + $library->count(),
            'summary' => [['Class books', number_format($books->count())], ['Library', number_format($library->count())]], 'blocks' => $blocks];
    }

    /** Exams, then each exam's date sheet class by class. */
    public static function exams(Ctx $c): array
    {
        $exams = DB::table('exams')->where('organization_id', $c->org)->orderByDesc('start_date')->get();
        $sheets = DB::table('exam_datesheets as d')->join('exam_datesheet_papers as p', 'p.exam_datesheet_id', '=', 'd.id')
            ->where('d.organization_id', $c->org)->orderBy('p.exam_date')->orderBy('p.start_time')
            ->get(['d.exam_id', 'd.standard_id', 'd.section_id', 'p.subject_id', 'p.exam_date', 'p.start_time', 'p.end_time', 'p.shift'])->groupBy('exam_id');

        $blocks = [['type' => 'table', 'columns' => [['Exam', 'w' => 18], ['Type', 'w' => 9], ['Term', 'w' => 8], ['Year', 'w' => 8], ['From', 'w' => 10],
            ['To', 'w' => 10], ['Total', 'w' => 7, 'align' => 'right'], ['Pass', 'w' => 7, 'align' => 'right'], ['Status', 'w' => 9], ['Published', 'w' => 8]],
            'rows' => $exams->map(fn ($e) => [Ctx::dash($e->exam_name), Ctx::headline($e->exam_type), Ctx::dash($e->term), Ctx::dash($e->academic_year),
                Ctx::date($e->start_date), Ctx::date($e->end_date), Ctx::dash($e->total_marks), Ctx::dash($e->passing_marks), Ctx::headline($e->status),
                $e->is_published ? 'Yes' : 'No'])->all()]];

        foreach ($exams as $e) {
            $papers = $sheets[$e->id] ?? collect();
            if ($papers->isEmpty()) {
                continue;
            }
            $list = $c->byClass($papers->all(), fn ($p) => $p->standard_id, fn ($p) => $p->section_id, fn ($p) => (string) $p->exam_date);
            $blocks[] = ['type' => 'heading', 'text' => ($e->exam_name ?: 'Exam') . ' · Date sheet', 'meta' => count($list) . ' papers'];
            $blocks[] = ['type' => 'table', 'columns' => [['Class', 'w' => 20], ['Subject', 'w' => 28], ['Date', 'w' => 20], ['Time', 'w' => 22], ['Shift', 'w' => 10]],
                'rows' => array_map(fn ($p) => [$c->classLabel($p->standard_id, $p->section_id), $c->subject($p->subject_id), Ctx::date($p->exam_date, 'D, d M Y'),
                    $p->start_time ? Ctx::time($p->start_time) . ' – ' . Ctx::time($p->end_time) : '—', Ctx::dash($p->shift)], $list)];
        }

        return ['title' => 'Exams', 'subtitle' => 'Every exam and its date sheet', 'count' => $exams->count(),
            'summary' => [['Exams', number_format($exams->count())], ['Papers', number_format($sheets->flatten()->count())]], 'blocks' => $blocks];
    }

    /** Admit cards issued, exam by exam. */
    public static function admitCards(Ctx $c): array
    {
        $rows = DB::table('admit_cards')->where('organization_id', $c->org)->get();
        $rows = $c->byClass($rows->all(), fn ($r) => $r->standard_id, fn ($r) => $r->section_id, fn ($r) => ($r->exam_name ?? '') . ' ' . $r->student_name);

        return self::table(array_map(fn ($r) => [
            Ctx::dash($r->exam_name), Ctx::dash($r->admit_card_number), Ctx::dash($r->student_name ?: $c->student($r->student_detail_id)['name']),
            $c->classLabel($r->standard_id, $r->section_id), Ctx::dash($r->roll_number), Ctx::dash($r->exam_roll_number),
            trim(Ctx::dash($r->room_number) . ' / ' . Ctx::dash($r->seat_number)), Ctx::dash($r->exam_center), Ctx::date($r->issue_date), Ctx::headline($r->status),
        ], $rows), [['Exam', 'w' => 12], ['Card No', 'w' => 11], ['Student', 'w' => 16], ['Class', 'w' => 9], ['Roll', 'w' => 7], ['Exam roll', 'w' => 8],
            ['Room / Seat', 'w' => 9], ['Centre', 'w' => 12], ['Issued', 'w' => 8], ['Status', 'w' => 8]],
            'Admit Cards', 'Every admit card issued, with exam roll, room and seat', 'landscape');
    }

    /** Seating plans with each plan's seats, and the rooms. */
    public static function seating(Ctx $c): array
    {
        $plans = DB::table('seating_plans')->where('organization_id', $c->org)->orderByDesc('exam_date')->get();
        $rooms = DB::table('seating_rooms')->where('organization_id', $c->org)->orderBy('room_name')->get();
        $exams = DB::table('exams')->where('organization_id', $c->org)->pluck('exam_name', 'id');

        $blocks = [['type' => 'table', 'columns' => [['Plan', 'w' => 20], ['Exam', 'w' => 18], ['Date', 'w' => 11], ['Session', 'w' => 10],
            ['Students', 'w' => 9, 'align' => 'right'], ['Seats', 'w' => 8, 'align' => 'right'], ['Clashes', 'w' => 8, 'align' => 'right'], ['Status', 'w' => 16]],
            'rows' => $plans->map(fn ($p) => [Ctx::dash($p->name), Ctx::dash($exams[$p->exam_id] ?? null), Ctx::date($p->exam_date), Ctx::headline($p->session),
                (int) $p->total_students, (int) $p->total_seats, (int) $p->conflict_count, Ctx::headline($p->status)])->all()]];

        if ($rooms->isNotEmpty()) {
            $blocks[] = ['type' => 'heading', 'text' => 'Rooms', 'meta' => $rooms->count() . ' rooms'];
            $blocks[] = ['type' => 'table', 'columns' => [['Room', 'w' => 24], ['Building', 'w' => 22], ['Rows × Columns', 'w' => 16], ['Seats', 'w' => 12, 'align' => 'right'],
                ['Active', 'w' => 10], ['Notes', 'w' => 16]],
                'rows' => $rooms->map(fn ($r) => [Ctx::dash($r->room_name), Ctx::dash($r->building), (int) $r->rows . ' × ' . (int) $r->columns,
                    (int) ($r->seat_capacity ?: $r->capacity), $r->is_active ? 'Yes' : 'No', Ctx::dash($r->notes)])->all()];
        }

        $roomNames = $rooms->pluck('room_name', 'id');
        foreach ($plans as $p) {
            $seats = DB::table('seat_assignments')->where('seating_plan_id', $p->id)->orderBy('room_id')->orderBy('seat_position')->get();
            if ($seats->isEmpty()) {
                continue;
            }
            $blocks[] = ['type' => 'heading', 'text' => ($p->name ?: 'Plan') . ' · Seats', 'meta' => $seats->count() . ' students'];
            $blocks[] = ['type' => 'table', 'columns' => [['Room', 'w' => 20], ['Seat', 'w' => 10, 'align' => 'right'], ['Student', 'w' => 34], ['Class', 'w' => 24], ['Clash', 'w' => 12]],
                'rows' => $seats->map(fn ($s) => [Ctx::dash($roomNames[$s->room_id] ?? null), (int) $s->seat_position, $c->student($s->student_id)['name'],
                    Ctx::dash($s->class_label ?: $c->student($s->student_id)['class']), $s->has_conflict ? 'Yes' : ''])->all()];
        }

        return ['title' => 'Seating Plan', 'subtitle' => 'Exam seating plans, their seats, and the rooms', 'count' => $plans->count(),
            'summary' => [['Plans', number_format($plans->count())], ['Rooms', number_format($rooms->count())]], 'blocks' => $blocks];
    }

    /** Marks: for each exam, every student of a class with each subject's marks and the total. */
    public static function performance(Ctx $c): array
    {
        $copies = DB::table('exam_copies')->where('organization_id', $c->org)->get();
        $exams  = DB::table('exams')->where('organization_id', $c->org)->get()->keyBy('id');

        $blocks = [];
        $byExam = $copies->groupBy('exam_id')->sortByDesc(fn ($rows, $id) => (string) ($exams[$id]->start_date ?? ''));
        foreach ($byExam as $examId => $rows) {
            $byClass = $rows->groupBy(fn ($r) => (int) $r->standard_id . '-' . (int) $r->section_id)->all();
            uksort($byClass, function ($a, $b) use ($c) {
                $rank = $c->classRank();
                [$sa, $xa] = explode('-', $a);
                [$sb, $xb] = explode('-', $b);

                return [($rank[$sa] ?? PHP_INT_MAX), $c->sections()[$xa] ?? ''] <=> [($rank[$sb] ?? PHP_INT_MAX), $c->sections()[$xb] ?? ''];
            });
            foreach ($byClass as $key => $list) {
                [$std, $sec] = explode('-', $key);
                $subjects = $list->pluck('subject_id')->unique()->sortBy(fn ($id) => $c->subject($id))->values();
                $students = $list->groupBy('student_detail_id');
                $table = [];
                foreach ($students as $sid => $marks) {
                    $s = $c->student($sid);
                    $got = $max = 0;
                    $cells = [Ctx::dash($s['roll']), $s['name']];
                    foreach ($subjects as $sub) {
                        $m = $marks->firstWhere('subject_id', $sub);
                        if (!$m) {
                            $cells[] = '—';
                            continue;
                        }
                        $got += $m->is_absent ? 0 : (float) $m->marks_obtained;
                        $max += (float) $m->max_marks;
                        $cells[] = $m->is_absent ? 'AB' : rtrim(rtrim(number_format((float) $m->marks_obtained, 2), '0'), '.') . '/' . rtrim(rtrim(number_format((float) $m->max_marks, 2), '0'), '.');
                    }
                    $cells[] = rtrim(rtrim(number_format($got, 2), '0'), '.') . '/' . rtrim(rtrim(number_format($max, 2), '0'), '.');
                    $cells[] = Ctx::pct($got, $max);
                    $table[] = $cells;
                }
                usort($table, fn ($x, $y) => strnatcasecmp($x[1], $y[1]));
                $w = max(5, intdiv(62, max(1, $subjects->count())));
                $blocks[] = ['type' => 'heading', 'text' => ($exams[$examId]->exam_name ?? 'Exam') . ' · ' . $c->classLabel((int) $std, (int) $sec),
                    'meta' => count($table) . ' students'];
                $blocks[] = ['type' => 'table', 'small' => $subjects->count() > 8,
                    'columns' => array_merge([['Roll', 'w' => 5], ['Student', 'w' => 17]],
                        $subjects->map(fn ($id) => [$c->subject($id), 'w' => $w, 'align' => 'right'])->all(),
                        [['Total', 'w' => 9, 'align' => 'right'], ['%', 'w' => 6, 'align' => 'right']]),
                    'rows' => $table];
            }
        }

        return ['title' => 'Performance', 'subtitle' => 'Marks of every student in each exam, subject by subject (AB = absent)', 'orientation' => 'landscape',
            'count' => $copies->count(),
            'summary' => [['Exams', number_format($byExam->count())], ['Marks entered', number_format($copies->count())]], 'blocks' => $blocks];
    }

    /** Report cards issued. */
    public static function reportCards(Ctx $c): array
    {
        $rows = DB::table('report_cards')->where('organization_id', $c->org)->get();
        $rows = $c->byClass($rows->all(), fn ($r) => $r->standard_id, fn ($r) => $r->section_id, fn ($r) => $c->student($r->student_detail_id)['name']);

        return self::table(array_map(fn ($r) => [
            Ctx::dash($r->regd_no), $c->student($r->student_detail_id)['name'], $c->classLabel($r->standard_id, $r->section_id), Ctx::dash($r->academic_year),
            Ctx::headline($r->result), Ctx::text($r->remark), Ctx::headline($r->status), Ctx::date($r->issued_at), $c->user($r->issued_by),
        ], $rows), [['Regd No', 'w' => 9], ['Student', 'w' => 17], ['Class', 'w' => 10], ['Year', 'w' => 8], ['Result', 'w' => 7], ['Remark', 'w' => 21],
            ['Status', 'w' => 7], ['Issued on', 'w' => 9], ['Issued by', 'w' => 12]],
            'Report Cards', 'Every report card issued', 'landscape');
    }

    /** Transfer certificates and other certificates. */
    public static function certificates(Ctx $c): array
    {
        $tcs   = DB::table('transfer_certificates')->where('organization_id', $c->org)->orderByDesc('issue_date')->get();
        $certs = DB::table('certificates')->where('organization_id', $c->org)->orderByDesc('issued_date')->get();

        $blocks = [];
        if ($tcs->isNotEmpty()) {
            $blocks[] = ['type' => 'heading', 'text' => 'Transfer Certificates', 'meta' => $tcs->count() . ' issued'];
            $blocks[] = ['type' => 'table', 'columns' => [['TC No', 'w' => 7], ['Book', 'w' => 5], ['Student', 'w' => 13], ['Last class', 'w' => 8],
                ['Applied', 'w' => 8], ['Issued', 'w' => 8], ['Reason', 'w' => 12], ['Conduct', 'w' => 7], ['Days present', 'w' => 8], ['Fees paid up to', 'w' => 8],
                ['Promotion', 'w' => 7], ['Remarks', 'w' => 9]],
                'rows' => $tcs->map(fn ($t) => [Ctx::dash($t->tc_no), Ctx::dash($t->book_no), $c->student($t->student_detail_id)['name'],
                    Ctx::dash($t->last_class_studied), Ctx::date($t->application_date), Ctx::date($t->issue_date), Ctx::dash($t->reason_for_leaving),
                    Ctx::dash($t->general_conduct), ($t->days_present !== null ? (int) $t->days_present : '—') . ' / ' . ($t->total_working_days !== null ? (int) $t->total_working_days : '—'),
                    Ctx::dash($t->fees_paid_upto), Ctx::dash($t->qualified_for_promotion), Ctx::dash($t->remarks)])->all()];
        }
        if ($certs->isNotEmpty()) {
            $blocks[] = ['type' => 'heading', 'text' => 'Certificates', 'meta' => $certs->count() . ' issued'];
            $blocks[] = ['type' => 'table', 'columns' => [['No', 'w' => 9], ['Student', 'w' => 16], ['Class', 'w' => 10], ['Type', 'w' => 9], ['Event', 'w' => 16],
                ['Issued by', 'w' => 14], ['Date', 'w' => 9], ['Details', 'w' => 17]],
                'rows' => $certs->map(fn ($x) => [Ctx::dash($x->certificate_no), $c->student($x->student_detail_id)['name'], $c->student($x->student_detail_id)['class'],
                    Ctx::headline($x->type), Ctx::dash($x->event_name), trim(Ctx::dash($x->issued_by) . ($x->issued_by_designation ? ', ' . $x->issued_by_designation : '')),
                    Ctx::date($x->issued_date), Ctx::text($x->description)])->all()];
        }

        return ['title' => 'TC & Certificates', 'subtitle' => 'Transfer certificates and other certificates issued', 'orientation' => 'landscape',
            'count' => $tcs->count() + $certs->count(),
            'summary' => [['Transfer certificates', number_format($tcs->count())], ['Certificates', number_format($certs->count())]], 'blocks' => $blocks];
    }

    /** A document that is one table. */
    public static function table(array $rows, array $columns, string $title, string $subtitle, string $orientation = 'portrait', array $summary = []): array
    {
        return [
            'title'       => $title,
            'subtitle'    => $subtitle,
            'orientation' => $orientation,
            'count'       => count($rows),
            'summary'     => $summary ?: [['Records', number_format(count($rows))]],
            'blocks'      => $rows ? [['type' => 'table', 'columns' => $columns, 'rows' => array_values($rows)]] : [],
        ];
    }
}
