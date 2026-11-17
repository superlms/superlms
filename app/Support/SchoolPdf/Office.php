<?php

namespace App\Support\SchoolPdf;

use Illuminate\Support\Facades\DB;

/**
 * Announcements, Calendar, Enquiries, Admissions, ID Cards, Documents,
 * Complaints, Reception, Gate Pass, Messaging, Push Notifications and
 * Support — the school office's records.
 */
class Office
{
    public static function announcements(Ctx $c): array
    {
        $rows = DB::table('announcements')->where('organization_id', $c->org)->orderByDesc('created_at')->get();

        return Academics::table($rows->map(fn ($a) => [
            Ctx::date($a->created_at), Ctx::text($a->announcement_name), Ctx::text($a->announcement_content),
            trim(Ctx::headline($a->type) . ($a->standard_id ? ' · ' . $c->classLabel($a->standard_id) : '')),
            implode(', ', array_filter([$a->announcement_image ? 'Image' : null, $a->announcement_pdf ? 'PDF' : null])) ?: '—', $c->user($a->user_id),
        ])->all(), [['Date', 'w' => 10], ['Title', 'w' => 18], ['Message', 'w' => 40], ['For', 'w' => 12], ['Files', 'w' => 8], ['By', 'w' => 12]],
            'Announcements', 'Every announcement made, newest first', 'landscape');
    }

    public static function calendar(Ctx $c): array
    {
        $rows = DB::table('time_tables')->where('organization_id', $c->org)->orderBy('date')->orderBy('start_time')->get();

        return Academics::table($rows->map(fn ($e) => [
            Ctx::date($e->date, 'D, d M Y'), $e->is_all_day ? 'All day' : Ctx::time($e->start_time) . ' – ' . Ctx::time($e->end_time),
            Ctx::dash($e->title), Ctx::headline($e->event_type), Ctx::text($e->description),
            $e->recurrence && $e->recurrence !== 'none' ? Ctx::headline($e->recurrence) . ($e->recurrence_end_date ? ' till ' . Ctx::date($e->recurrence_end_date) : '') : '—',
            $e->is_cancelled ? 'Cancelled' . ($e->cancellation_reason ? ': ' . $e->cancellation_reason : '') : 'On', $c->user($e->created_by),
        ])->all(), [['Date', 'w' => 11], ['Time', 'w' => 12], ['Title', 'w' => 16], ['Type', 'w' => 8], ['Details', 'w' => 23], ['Repeats', 'w' => 11],
            ['Status', 'w' => 9], ['Added by', 'w' => 10]],
            'Calendar', 'Every event, holiday and meeting on the school calendar', 'landscape');
    }

    /** Website enquiries and the questions students and teachers sent the school. */
    public static function enquiries(Ctx $c): array
    {
        $web = Ctx::has('school_website_enquiries') ? DB::table('school_website_enquiries')->where('organization_id', $c->org)->orderByDesc('created_at')->get() : collect();
        $gen = DB::table('admin_enquiries')->where('organization_id', $c->org)->orderByDesc('created_at')->get();
        $stu = DB::table('contact_admin_students')->where('organization_id', $c->org)->orderByDesc('created_at')->get();
        $tea = DB::table('contact_admin_teachers')->where('organization_id', $c->org)->orderByDesc('created_at')->get();

        $blocks = [];
        if ($web->isNotEmpty()) {
            $blocks[] = ['type' => 'heading', 'text' => 'Website enquiries', 'meta' => $web->count() . ' received'];
            $blocks[] = ['type' => 'table', 'columns' => [['Date', 'w' => 9], ['Type', 'w' => 8], ['Name', 'w' => 13], ['Phone', 'w' => 10], ['Email', 'w' => 15],
                ['Subject', 'w' => 13], ['Message', 'w' => 32]],
                'rows' => $web->map(fn ($e) => [Ctx::date($e->created_at), Ctx::headline($e->type), Ctx::dash($e->name), Ctx::dash($e->phone), Ctx::dash($e->email),
                    Ctx::dash($e->subject), trim(Ctx::text($e->message) . (($d = Ctx::text($e->data)) !== '—' ? "\n" . $d : ''))])->all()];
        }
        if ($gen->isNotEmpty()) {
            $blocks[] = ['type' => 'heading', 'text' => 'Enquiries', 'meta' => $gen->count() . ' received'];
            $blocks[] = ['type' => 'table', 'columns' => [['Date', 'w' => 10], ['Name', 'w' => 16], ['Type', 'w' => 10], ['Mobile', 'w' => 12], ['Email', 'w' => 16], ['Details', 'w' => 36]],
                'rows' => $gen->map(fn ($e) => [Ctx::date($e->created_at), Ctx::dash($e->full_name), Ctx::headline($e->type), Ctx::dash($e->mobile_number),
                    Ctx::dash($e->email), Ctx::text($e->description)])->all()];
        }
        foreach ([['Questions from students', $stu, 'student_query', fn ($q) => $c->student($q->student_detail_id)['name'] . ' · ' . $c->student($q->student_detail_id)['class']],
                  ['Questions from teachers', $tea, 'teacher_query', fn ($q) => $c->teachers()[$q->teacher_detail_id] ?? $c->user($q->user_id)]] as [$title, $list, $col, $who]) {
            if ($list->isEmpty()) {
                continue;
            }
            $blocks[] = ['type' => 'heading', 'text' => $title, 'meta' => $list->count() . ' asked'];
            $blocks[] = ['type' => 'table', 'columns' => [['Date', 'w' => 9], ['From', 'w' => 17], ['Topic', 'w' => 14], ['Question', 'w' => 28], ['Reply', 'w' => 24], ['Status', 'w' => 8]],
                'rows' => $list->map(fn ($q) => [Ctx::date($q->created_at), $who($q), Ctx::text($q->topic), Ctx::text($q->{$col}), Ctx::text($q->admin_text),
                    $q->admin_reply ? 'Replied' : 'Pending'])->all()];
        }

        $n = $web->count() + $gen->count() + $stu->count() + $tea->count();

        return ['title' => 'Enquiries', 'subtitle' => 'Website enquiries and the questions students and teachers sent the school', 'orientation' => 'landscape',
            'count' => $n, 'summary' => [['Website', number_format($web->count())], ['Enquiries', number_format($gen->count())],
                ['Students', number_format($stu->count())], ['Teachers', number_format($tea->count())]], 'blocks' => $blocks];
    }

    /** Admission enquiries: the child, guardian, fee collected and result. */
    public static function admissions(Ctx $c): array
    {
        $rows = DB::table('admission_enquiries')->where('organization_id', $c->org)->orderByDesc('created_at')->get();

        return Academics::table($rows->map(fn ($a) => [
            Ctx::date($a->created_at), Ctx::dash($a->student_name), trim(Ctx::dash($a->guardian_name) . "\n" . Ctx::dash($a->mobile) . ($a->email ? "\n" . $a->email : '')),
            trim($c->classLabel($a->standard_id) . ($a->stream ? ' · ' . $a->stream : '')), Ctx::dash($a->address),
            (float) $a->admission_fee > 0 ? Ctx::money($a->admission_fee) : '—',
            (float) $a->collected_amount > 0 ? Ctx::money($a->collected_amount) . ' · ' . Ctx::mode($a->payment_mode) . ($a->fee_collected_at ? "\n" . Ctx::date($a->fee_collected_at) : '') : '—',
            Ctx::dash($a->collected_by), $a->total_marks ? rtrim(rtrim(number_format((float) $a->obtained_marks, 2), '0'), '.') . ' / ' . rtrim(rtrim(number_format((float) $a->total_marks, 2), '0'), '.') : '—',
            Ctx::headline($a->status), Ctx::text($a->remarks),
        ])->all(), [['Date', 'w' => 8], ['Student', 'w' => 12], ['Guardian', 'w' => 14], ['Class', 'w' => 8], ['Address', 'w' => 12], ['Fee', 'w' => 6, 'align' => 'right'],
            ['Collected', 'w' => 10], ['Collected by', 'w' => 9], ['Marks', 'w' => 6], ['Status', 'w' => 6], ['Remarks', 'w' => 9]],
            'Admissions', 'Every admission enquiry with its fee and test result', 'landscape');
    }

    public static function idCards(Ctx $c): array
    {
        $blocks = [];
        $n = 0;
        foreach ([['Students', 'student_id_cards', 'student_detail_id', fn ($r) => [$c->student($r->student_detail_id)['name'], $c->student($r->student_detail_id)['class']]],
                  ['Teachers', 'teacher_id_cards', 'teacher_detail_id', fn ($r) => [$c->teachers()[$r->teacher_detail_id] ?? '—', 'Teacher']],
                  ['Staff', 'employee_id_cards', 'admin_employee_id', fn ($r) => [$c->employees()[$r->admin_employee_id] ?? '—', 'Staff']]] as [$title, $table, , $who]) {
            if (!Ctx::has($table)) {
                continue;
            }
            $rows = DB::table($table)->where('organization_id', $c->org)->orderBy('card_number')->get();
            if ($rows->isEmpty()) {
                continue;
            }
            $n += $rows->count();
            $blocks[] = ['type' => 'heading', 'text' => $title, 'meta' => $rows->count() . ' cards'];
            $blocks[] = ['type' => 'table', 'columns' => [['Card No', 'w' => 16], ['Name', 'w' => 28], ['Class / Role', 'w' => 18], ['Issued', 'w' => 13], ['Valid till', 'w' => 13], ['Status', 'w' => 12]],
                'rows' => $rows->map(fn ($r) => array_merge([Ctx::dash($r->card_number)], $who($r), [Ctx::date($r->issue_date), Ctx::date($r->expiry_date), Ctx::headline($r->status)]))->all()];
        }

        return ['title' => 'ID Cards', 'subtitle' => 'ID cards issued to students, teachers and staff', 'count' => $n,
            'summary' => [['Cards', number_format($n)]], 'blocks' => $blocks];
    }

    public static function documents(Ctx $c): array
    {
        $rows = DB::table('admin_documents')->where('organization_id', $c->org)->orderByDesc('created_at')->get();

        return Academics::table($rows->map(fn ($d) => [
            Ctx::dash($d->title), Ctx::dash($d->file_name), $d->file_size ? number_format($d->file_size / 1024, 0) . ' KB' : '—',
            Ctx::dash($d->mime_type), Ctx::text($d->description), $c->user($d->uploaded_by), Ctx::date($d->created_at),
        ])->all(), [['Title', 'w' => 18], ['File', 'w' => 20], ['Size', 'w' => 8, 'align' => 'right'], ['Type', 'w' => 12], ['Description', 'w' => 20],
            ['Uploaded by', 'w' => 12], ['Date', 'w' => 10]], 'Documents', 'Every document the school uploaded', 'landscape');
    }

    public static function complaints(Ctx $c): array
    {
        $rows = Ctx::has('school_complaints') ? DB::table('school_complaints')->where('organization_id', $c->org)->orderByDesc('created_at')->get() : collect();

        return Academics::table($rows->map(fn ($r) => [
            Ctx::date($r->created_at), $c->student($r->student_detail_id)['name'], $c->classLabel($r->standard_id, $r->section_id) !== '—'
                ? $c->classLabel($r->standard_id, $r->section_id) : $c->student($r->student_detail_id)['class'],
            Ctx::dash($r->title), Ctx::text($r->description), $r->file_path ? Ctx::dash($r->file_name ?: 'Yes') : '—', $c->user($r->added_by),
        ])->all(), [['Date', 'w' => 9], ['Student', 'w' => 15], ['Class', 'w' => 10], ['Title', 'w' => 16], ['Complaint', 'w' => 32], ['File', 'w' => 9], ['Added by', 'w' => 9]],
            'Complaints', 'The complaints book, newest first', 'landscape');
    }

    public static function reception(Ctx $c): array
    {
        $rows = Ctx::has('reception_visitors') ? DB::table('reception_visitors')->where('organization_id', $c->org)->orderByDesc('in_at')->get() : collect();

        return Academics::table($rows->map(fn ($v) => [
            Ctx::date($v->in_at, 'd M Y, h:i A'), $v->out_at ? Ctx::date($v->out_at, 'h:i A') : '—', Ctx::dash($v->name),
            trim(Ctx::dash($v->mobile) . ($v->email ? "\n" . $v->email : '')), Ctx::dash($v->whom_to_meet), Ctx::dash($v->purpose), Ctx::text($v->details), $c->user($v->added_by),
        ])->all(), [['In', 'w' => 13], ['Out', 'w' => 7], ['Visitor', 'w' => 14], ['Mobile / Email', 'w' => 15], ['To meet', 'w' => 12], ['Purpose', 'w' => 10],
            ['Details', 'w' => 20], ['Added by', 'w' => 9]], 'Reception', 'The visitor book: who came, when, whom they met and why', 'landscape');
    }

    public static function gatePass(Ctx $c): array
    {
        $rows = Ctx::has('student_gate_passes') ? DB::table('student_gate_passes')->where('organization_id', $c->org)->orderByDesc('out_at')->get() : collect();

        return Academics::table($rows->map(fn ($g) => [
            (string) $g->pass_number, $c->student($g->student_detail_id)['name'],
            $c->classLabel($g->standard_id, $g->section_id) !== '—' ? $c->classLabel($g->standard_id, $g->section_id) : $c->student($g->student_detail_id)['class'],
            Ctx::date($g->out_at, 'd M Y, h:i A'), $g->returned_at ? Ctx::date($g->returned_at, 'd M, h:i A') : '—', Ctx::dash($g->reason),
            trim(Ctx::dash($g->picked_by_name) . ($g->picked_by_relation ? ' (' . $g->picked_by_relation . ')' : '') . ($g->picked_by_mobile ? "\n" . $g->picked_by_mobile : '')),
            Ctx::text($g->remarks), $c->user($g->added_by),
        ])->all(), [['Pass', 'w' => 5], ['Student', 'w' => 14], ['Class', 'w' => 9], ['Out', 'w' => 12], ['Back', 'w' => 9], ['Reason', 'w' => 15],
            ['Picked up by', 'w' => 15], ['Remarks', 'w' => 12], ['Added by', 'w' => 9]], 'Gate Pass', 'Every gate pass: who left, when, why and who took them', 'landscape');
    }

    public static function messaging(Ctx $c): array
    {
        $rows = Ctx::has('messaging_campaigns') ? DB::table('messaging_campaigns')->where('organization_id', $c->org)->orderByDesc('created_at')->get() : collect();

        return Academics::table($rows->map(fn ($m) => [
            Ctx::date($m->created_at, 'd M Y, h:i A'), strtoupper((string) $m->channel) === 'WHATSAPP' ? 'WhatsApp' : Ctx::headline($m->channel),
            Ctx::dash($m->audience_label ?: Ctx::headline($m->audience)), Ctx::dash($m->subject), Ctx::text($m->body),
            (int) $m->recipients, (int) $m->sent . ' / ' . (int) $m->failed, Ctx::headline($m->status), $c->user($m->created_by),
        ])->all(), [['Date', 'w' => 11], ['Channel', 'w' => 7], ['To', 'w' => 12], ['Subject', 'w' => 12], ['Message', 'w' => 30], ['People', 'w' => 6, 'align' => 'right'],
            ['Sent / failed', 'w' => 7, 'align' => 'right'], ['Status', 'w' => 7], ['By', 'w' => 8]], 'Messaging', 'WhatsApp, SMS and email messages sent to parents and staff', 'landscape');
    }

    public static function push(Ctx $c): array
    {
        $rows = Ctx::has('school_push_notifications') ? DB::table('school_push_notifications')->where('organization_id', $c->org)->orderByDesc('created_at')->get() : collect();

        return Academics::table($rows->map(fn ($p) => [
            Ctx::date($p->created_at, 'd M Y, h:i A'), Ctx::dash($p->title), Ctx::text($p->body), Ctx::dash($p->audience_label ?: Ctx::headline($p->audience)),
            (int) $p->recipient_count, (int) $p->device_count + (int) $p->web_count, $p->delivered ? 'Yes' : 'No', $c->user($p->sent_by),
        ])->all(), [['Date', 'w' => 12], ['Title', 'w' => 16], ['Message', 'w' => 32], ['To', 'w' => 14], ['People', 'w' => 6, 'align' => 'right'],
            ['Devices', 'w' => 6, 'align' => 'right'], ['Delivered', 'w' => 6], ['By', 'w' => 8]], 'Push Notifications', 'App notifications sent to students and teachers', 'landscape');
    }

    public static function support(Ctx $c): array
    {
        $rows = DB::table('contact_super_admins')->where('organization_id', $c->org)->orderByDesc('created_at')->get();

        return Academics::table($rows->map(fn ($t) => [
            Ctx::date($t->created_at), Ctx::text($t->topic), Ctx::text($t->admin_query), Ctx::text($t->super_admin_text), $t->super_admin_reply ? 'Replied' : 'Pending', $c->user($t->user_id),
        ])->all(), [['Date', 'w' => 10], ['Topic', 'w' => 16], ['Question', 'w' => 32], ['Reply', 'w' => 26], ['Status', 'w' => 8], ['By', 'w' => 8]],
            'Support', 'Questions the school sent to SuperLMS, and the replies', 'landscape');
    }
}
