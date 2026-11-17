<?php

namespace App\Support\SchoolPdf;

use App\Support\AcademicYear;
use Illuminate\Support\Facades\DB;

/**
 * Students, Teachers, Staff and Classes: one card per person with every
 * field the school keeps on them, class by class; staff and classes as tables.
 */
class People
{
    /** Students: a card each, every field of theirs, with attendance and fee. */
    public static function students(Ctx $c): array
    {
        $rows = DB::table('student_details as s')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->where('s.organization_id', $c->org)
            ->get(['s.*', 'u.email as login_email', 'u.username', 'u.is_active']);
        $rows = $c->byClass($rows->all(), fn ($s) => $s->standard_id, fn ($s) => $s->section_id, fn ($s) => $s->full_name);
        $ids  = array_map(fn ($s) => (int) $s->id, $rows);

        $att = DB::table('student_attendances')->where('organization_id', $c->org)->whereIn('student_detail_id', $ids)
            ->whereDate('attendance_date', '>=', AcademicYear::start()->toDateString())
            ->selectRaw('student_detail_id, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present,'
                . ' SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as absent, SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as half')
            ->groupBy('student_detail_id')->get()->keyBy('student_detail_id');
        $routes = DB::table('transportation_students as ts')->join('transportations as t', 't.id', '=', 'ts.transportation_id')
            ->where('t.organization_id', $c->org)->whereIn('ts.student_detail_id', $ids)
            ->pluck('t.route_name', 'ts.student_detail_id');
        $fees = Money::positions($c, $ids);

        $blocks = [];
        $groups = [];
        foreach ($rows as $s) {
            $groups[$c->classLabel($s->standard_id, $s->section_id)][] = $s;
        }

        $active = 0;
        foreach ($groups as $label => $students) {
            $blocks[] = ['type' => 'heading', 'text' => $label, 'meta' => count($students) . ' ' . (count($students) === 1 ? 'student' : 'students')];
            $cards = [];
            foreach ($students as $s) {
                $a   = $att[$s->id] ?? null;
                $fee = $fees[$s->id] ?? null;
                $active += $s->is_active ? 1 : 0;
                $cards[] = [
                    'title' => $s->full_name ?: '—',
                    'meta'  => 'Adm ' . Ctx::dash($s->admission_no) . ' · Roll ' . Ctx::dash($s->roll_no) . ' · ' . ($s->is_active ? 'Active' : 'Inactive'),
                    'fields' => [
                        ['Class', $c->classLabel($s->standard_id, null)],
                        ['Section', $c->sections()[$s->section_id] ?? '—'],
                        ['Board', Ctx::dash($s->board)],
                        ['Registration No', Ctx::dash($s->registration_number)],
                        ['Gender', Ctx::headline($s->gender)],
                        ['Date of Birth', Ctx::date($s->dob)],
                        ['Admitted on', Ctx::date($s->date_of_admission)],
                        ['Religion', Ctx::dash($s->religion)],
                        ['Father', Ctx::dash($s->father_name)],
                        ['Mother', Ctx::dash($s->mother_name)],
                        ['Mobile', Ctx::dash($s->phone)],
                        ['Email', Ctx::dash($s->email ?: $s->login_email)],
                        ['Login ID', Ctx::dash($s->username)],
                        ['Aadhaar No', Ctx::dash($s->aadhar_no)],
                        ['Apaar ID', Ctx::dash($s->appar_id)],
                        ['PEN No', Ctx::dash($s->pen_no ?? null)],
                        ['Address', Ctx::dash($s->local_address)],
                        ['Permanent Address', Ctx::dash($s->permanent_address)],
                        ['City', Ctx::dash($s->city)],
                        ['State', Ctx::dash($s->state)],
                        ['Pincode', Ctx::dash($s->pincode)],
                        ['Transport', isset($routes[$s->id]) ? $routes[$s->id] : ($s->transportation_required ? 'Yes' : 'No')],
                        // As the Attendance PDF counts it: school days marked, a half day as half.
                        ['Attendance', $a ? (int) $a->present . ' present, ' . (int) $a->absent . ' absent' . ((int) $a->half ? ', ' . (int) $a->half . ' half day' : '') : '—'],
                        ['Attendance %', $a ? Ctx::pct((int) $a->present + 0.5 * (int) $a->half, (int) $a->present + (int) $a->absent + (int) $a->half) : '—'],
                        ['Total Fee', $fee ? Ctx::money($fee['total']) : '—'],
                        ['Fee Paid', $fee ? Ctx::money($fee['paid']) : '—'],
                        ['Fee Due', $fee ? Ctx::money($fee['due']) : '—'],
                        ['Added on', Ctx::date($s->created_at)],
                    ],
                ];
            }
            $blocks[] = ['type' => 'cards', 'cols' => 4, 'items' => $cards];
        }

        return [
            'title'    => 'Students',
            'subtitle' => 'Every student\'s details, attendance this session and fee, class by class',
            'count'    => count($rows),
            'summary'  => [
                ['Students', number_format(count($rows))],
                ['Active', number_format($active)],
                ['Classes', number_format(count($groups))],
                ['Session', AcademicYear::label()],
            ],
            'blocks'   => $blocks,
        ];
    }

    /** Teachers: a card each, with their classes, subjects, salary and attendance. */
    public static function teachers(Ctx $c): array
    {
        $rows = DB::table('teacher_details as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('t.organization_id', $c->org)
            ->orderBy('u.name')
            ->get(['t.*', 'u.name', 'u.email', 'u.username', 'u.mobile_number', 'u.alternative_mobile', 'u.gender', 'u.dob', 'u.is_active']);
        $ids = $rows->pluck('id')->all();

        $classes = [];
        foreach (DB::table('assign_teacher_standards')->where('organization_id', $c->org)->whereIn('teacher_detail_id', $ids)->get() as $r) {
            $classes[$r->teacher_detail_id][] = $c->classLabel($r->standard_id, $r->section_id);
        }
        foreach (DB::table('teacher_sections')->where('organization_id', $c->org)->whereIn('teacher_detail_id', $ids)->get() as $r) {
            $classes[$r->teacher_detail_id][] = $c->classLabel($r->standard_id, $r->section_id);
        }
        $subjects = [];
        foreach (DB::table('teacher_subjects')->where('organization_id', $c->org)->whereIn('teacher_detail_id', $ids)->get() as $r) {
            $subjects[$r->teacher_detail_id][] = $c->subject($r->subject_id) . ' (' . $c->classLabel($r->standard_id, $r->section_id) . ')';
        }
        $salary = DB::table('admin_employees')->where('organization_id', $c->org)->whereIn('teacher_detail_id', $ids)
            ->pluck('salary', 'teacher_detail_id');
        $att = DB::table('teacher_attendances')->where('organization_id', $c->org)->whereIn('teacher_detail_id', $ids)
            ->whereDate('attendance_date', '>=', AcademicYear::start()->toDateString())
            ->selectRaw('teacher_detail_id, SUM(CASE WHEN status IN (0, 1, 2) THEN 1 ELSE 0 END) as total,'
                . ' SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) + 0.5 * SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as present')
            ->groupBy('teacher_detail_id')->get()->keyBy('teacher_detail_id');

        $cards  = [];
        $active = 0;
        foreach ($rows as $t) {
            $a = $att[$t->id] ?? null;
            $active += $t->is_active ? 1 : 0;
            $cards[] = [
                'title'  => $t->name ?: '—',
                'meta'   => 'Emp ID ' . Ctx::dash($t->employee_id) . ' · ' . ($t->is_active ? 'Active' : 'Inactive'),
                'fields' => [
                    ['Email', Ctx::dash($t->email)],
                    ['Login ID', Ctx::dash($t->username)],
                    ['Mobile', Ctx::dash($t->phone ?: $t->mobile_number)],
                    ['Alternate Mobile', Ctx::dash($t->alternative_mobile)],
                    ['Gender', Ctx::headline($t->gender)],
                    ['Date of Birth', Ctx::date($t->dob)],
                    ['Joined on', Ctx::date($t->date_of_joining)],
                    ['Qualification', Ctx::dash($t->qualification)],
                    ['Address', Ctx::dash($t->address)],
                    ['City', Ctx::dash($t->city)],
                    ['State', Ctx::dash($t->state)],
                    ['Pincode', Ctx::dash($t->pincode)],
                    ['Emergency Contact', Ctx::dash($t->emergency_contact)],
                    ['Salary', isset($salary[$t->id]) && (float) $salary[$t->id] > 0 ? Ctx::money($salary[$t->id]) . ' a month' : '—'],
                    ['Attendance', $a ? rtrim(rtrim(number_format((float) $a->present, 1), '0'), '.') . ' present of ' . (int) $a->total . ' days (' . Ctx::pct((float) $a->present, (float) $a->total) . ')' : '—'],
                    ['Added on', Ctx::date($t->created_at)],
                    ['Classes', implode(', ', array_unique($classes[$t->id] ?? [])) ?: '—', 4],
                    ['Subjects', implode(', ', array_unique($subjects[$t->id] ?? [])) ?: '—', 4],
                ],
            ];
        }

        return [
            'title'    => 'Teachers',
            'subtitle' => 'Every teacher\'s details, classes, subjects, salary and attendance this session',
            'count'    => count($cards),
            'summary'  => [['Teachers', number_format(count($cards))], ['Active', number_format($active)], ['Session', AcademicYear::label()]],
            'blocks'   => $cards ? [['type' => 'cards', 'cols' => 4, 'items' => $cards]] : [],
        ];
    }

    /** Staff besides teachers: one row each, with bank details and attendance. */
    public static function staff(Ctx $c): array
    {
        $rows = DB::table('admin_employees')->where('organization_id', $c->org)
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'teacher'))
            ->orderBy('name')->get();
        $att = DB::table('admin_attendances')->where('organization_id', $c->org)->whereIn('admin_employee_id', $rows->pluck('id'))
            ->whereDate('date', '>=', AcademicYear::start()->toDateString())
            ->selectRaw("admin_employee_id, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,"
                . " SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,"
                . " SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_day,"
                . " SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days")
            ->groupBy('admin_employee_id')->get()->keyBy('admin_employee_id');

        $out = [];
        foreach ($rows as $i => $e) {
            $a = $att[$e->id] ?? null;
            $out[] = [
                $i + 1,
                Ctx::dash($e->name),
                Ctx::headline($e->type),
                Ctx::dash($e->designation),
                Ctx::dash($e->mobile) . ($e->email ? "\n" . $e->email : ''),
                Ctx::date($e->joining_date),
                (float) $e->salary > 0 ? Ctx::money($e->salary) : '—',
                trim(implode("\n", array_filter([$e->bank_name, $e->bank_account_no ? 'A/c ' . $e->bank_account_no : null,
                    $e->bank_ifsc ? 'IFSC ' . $e->bank_ifsc : null, $e->bank_holder_name, $e->bank_branch]))) ?: '—',
                Ctx::dash($e->address),
                $a ? (int) $a->present . ' P · ' . (int) $a->absent . ' A · ' . (int) $a->half_day . ' H · ' . (int) $a->leave_days . ' L' : '—',
                $e->is_active ? 'Active' : 'Inactive',
            ];
        }

        return [
            'title'       => 'Staff',
            'subtitle'    => 'Everyone on the staff besides teachers — drivers, office, support',
            'orientation' => 'landscape',
            'count'       => count($out),
            'summary'     => [['Staff', number_format(count($out))], ['Monthly salary', Ctx::money($rows->sum('salary'))]],
            'blocks'      => [[
                'type'    => 'table',
                'columns' => [['#', 'w' => 3], ['Name', 'w' => 12], ['Type', 'w' => 7], ['Designation', 'w' => 9], ['Mobile / Email', 'w' => 13],
                    ['Joined', 'w' => 7], ['Salary', 'w' => 7, 'align' => 'right'], ['Bank', 'w' => 14], ['Address', 'w' => 12],
                    ['Attendance (session)', 'w' => 10], ['Status', 'w' => 6]],
                'rows'    => $out,
                'note'    => 'P present · A absent · H half day · L leave',
            ]],
        ];
    }

    /** Classes and sections: students (boys / girls), subjects and teachers. */
    public static function classes(Ctx $c): array
    {
        $counts = DB::table('student_details')->where('organization_id', $c->org)
            ->selectRaw("standard_id, section_id, COUNT(*) as total,"
                . " SUM(CASE WHEN LOWER(gender) = 'male' THEN 1 ELSE 0 END) as boys,"
                . " SUM(CASE WHEN LOWER(gender) = 'female' THEN 1 ELSE 0 END) as girls")
            ->groupBy('standard_id', 'section_id')->get();
        $byKey = [];
        foreach ($counts as $r) {
            $byKey[(int) $r->standard_id . '-' . (int) $r->section_id] = $r;
        }

        $subjects = [];
        foreach (DB::table('standard_subjects')->where('organization_id', $c->org)->get() as $r) {
            $subjects[(int) $r->standard_id][] = $c->subject($r->subject_id);
        }
        $teachers = [];
        foreach (DB::table('assign_teacher_standards')->where('organization_id', $c->org)->get() as $r) {
            $teachers[(int) $r->standard_id . '-' . (int) $r->section_id][] = $c->teachers()[$r->teacher_detail_id] ?? null;
        }

        $sections = DB::table('sections')->whereIn('standard_id', array_keys($c->classes()))->orderBy('name')->get()->groupBy('standard_id');
        $boards   = DB::table('standards')->where('organization_id', $c->org)->pluck('board', 'id');

        $rows = [];
        $total = 0;
        foreach ($c->classes() as $id => $name) {
            $secs = $sections[$id] ?? collect();
            foreach ($secs->isEmpty() ? [null] : $secs as $sec) {
                $r = $byKey[$id . '-' . (int) ($sec->id ?? 0)] ?? null;
                $total += (int) ($r->total ?? 0);
                $rows[] = [
                    $name,
                    $sec->name ?? '—',
                    Ctx::dash($boards[$id] ?? null),
                    (int) ($r->total ?? 0),
                    (int) ($r->boys ?? 0),
                    (int) ($r->girls ?? 0),
                    implode(', ', array_unique(array_filter($subjects[$id] ?? []))) ?: '—',
                    implode(', ', array_unique(array_filter(array_merge($teachers[$id . '-' . (int) ($sec->id ?? 0)] ?? [], $teachers[$id . '-0'] ?? [])))) ?: '—',
                ];
            }
        }

        return [
            'title'    => 'Classes',
            'subtitle' => 'Each class and section with its students, subjects and teachers',
            'count'    => count($c->classes()),
            'summary'  => [['Classes', number_format(count($c->classes()))], ['Sections', number_format($sections->flatten()->count())], ['Students', number_format($total)]],
            'blocks'   => [[
                'type'    => 'table',
                'columns' => [['Class', 'w' => 10], ['Section', 'w' => 8], ['Board', 'w' => 8], ['Students', 'w' => 8, 'align' => 'right'],
                    ['Boys', 'w' => 6, 'align' => 'right'], ['Girls', 'w' => 6, 'align' => 'right'], ['Subjects', 'w' => 32], ['Teachers', 'w' => 22]],
                'rows'    => $rows,
            ]],
        ];
    }
}
