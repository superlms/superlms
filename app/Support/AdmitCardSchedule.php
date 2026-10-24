<?php

namespace App\Support;

use App\Models\Admin\ExamDatesheet;
use App\Models\Student\AdmitCard;
use App\Models\Student\SectionSubject;
use App\Models\Student\Subject;
use Illuminate\Support\Carbon;

/**
 * The paper schedule an admit card prints — the exam's datesheet for the
 * card's class.
 *
 * A card keeps a copy of its schedule (admit_cards.subjects), taken when it is
 * issued, and that copy used to be all it ever showed. A card issued before
 * its class's datesheet was saved got the class's subjects with no dates (the
 * fallback below), and one issued before the datesheet was changed kept the
 * old dates: the datesheet was never read again, so the card printed dashes
 * however often the datesheet was saved afterwards (Mid Term 2026, issued on
 * 22 Oct, before its datesheet was in).
 *
 * Every page, PDF and app screen that shows a card now passes it through
 * refresh() first: the copy is brought up to the datesheet as it is now, and
 * saved when it changed. A class with no datesheet yet keeps the copy it has.
 */
final class AdmitCardSchedule
{
    /**
     * The datesheet a class sits by, one that has papers: its section's own,
     * else the class-wide one, else any of the class's.
     */
    public static function sheet(int $orgId, int $examId, int $standardId, ?int $sectionId): ?ExamDatesheet
    {
        $base = fn () => ExamDatesheet::with('papers.subject')
            ->where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->where('standard_id', $standardId)
            ->whereHas('papers');

        return ($sectionId ? $base()->where('section_id', $sectionId)->first() : null)
            ?? $base()->whereNull('section_id')->first()
            ?? $base()->orderBy('id')->first();
    }

    /**
     * The card's rows from the datesheet, in the order the papers are sat —
     * null while the class has no datesheet.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public static function fromDatesheet(int $orgId, int $examId, int $standardId, ?int $sectionId): ?array
    {
        $sheet = self::sheet($orgId, $examId, $standardId, $sectionId);
        if (!$sheet) {
            return null;
        }

        return $sheet->papers->map(fn ($p) => [
            'subject_id'    => (string) $p->subject_id,
            'subject_name'  => $p->subject->name ?? '',
            'exam_date'     => $p->exam_date?->format('Y-m-d') ?? '',
            'exam_time'     => self::clock($p->start_time),
            'exam_end_time' => self::clock($p->end_time),
            'exam_duration' => self::duration($p->start_time, $p->end_time),
            'shift'         => (int) ($p->shift ?: 1),
        ])->values()->all();
    }

    /**
     * The schedule for a card being issued: the datesheet's, or — none saved
     * yet — the class's subjects with no dates, which refresh() fills in once
     * the datesheet is saved.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forNewCard(int $orgId, int $examId, int $standardId, ?int $sectionId): array
    {
        $rows = self::fromDatesheet($orgId, $examId, $standardId, $sectionId);
        if ($rows !== null) {
            return array_map(fn ($r) => $r + ['status' => 'eligible'], $rows);
        }

        $q = SectionSubject::where('organization_id', $orgId)->where('standard_id', $standardId);
        if ($sectionId) {
            $q->where('section_id', $sectionId);
        }

        return Subject::whereIn('id', $q->pluck('subject_id')->unique())
            ->where('is_active', true)->orderBy('id')->get()
            ->map(fn ($s) => [
                'subject_id'    => (string) $s->id,
                'subject_name'  => $s->name,
                'exam_date'     => '',
                'exam_time'     => '',
                'exam_end_time' => '',
                'exam_duration' => '',
                'shift'         => 1,
                'status'        => 'eligible',
            ])->values()->all();
    }

    /**
     * Bring cards' schedules up to the datesheet as it is now. A card whose
     * schedule changed is saved, so every later reader sees the same; one
     * whose class has no datesheet keeps its own. Anything a card carried of
     * its own for a subject (its marks, a code, a status) stays with it.
     *
     * @param  iterable<AdmitCard>  $cards
     */
    public static function refresh(iterable $cards): void
    {
        // One datesheet read per exam and class, however many cards.
        $rowsFor = [];

        foreach ($cards as $card) {
            if (!$card instanceof AdmitCard || !$card->exam_id || !$card->organization_id) {
                continue;
            }

            // The class the card was issued for; an old card without one, the student's.
            $standardId = (int) ($card->standard_id ?: $card->studentDetail?->standard_id);
            if (!$standardId) {
                continue;
            }
            $sectionId = (int) ($card->standard_id ? $card->section_id : $card->studentDetail?->section_id) ?: null;

            $key = "{$card->organization_id}|{$card->exam_id}|{$standardId}|{$sectionId}";
            if (!array_key_exists($key, $rowsFor)) {
                $rowsFor[$key] = self::fromDatesheet((int) $card->organization_id, (int) $card->exam_id, $standardId, $sectionId);
            }
            $rows = $rowsFor[$key];
            if ($rows === null) {
                continue;
            }

            $old  = collect((array) ($card->subjects ?? []))->keyBy(fn ($r) => (string) ($r['subject_id'] ?? ''));
            $next = array_map(
                fn ($r) => array_merge(['status' => 'eligible'], (array) $old->get($r['subject_id'], []), $r),
                $rows,
            );

            // Loose: the same rows in another key order, or "1" for 1, are the same.
            if ($next == array_values((array) ($card->subjects ?? []))) {
                continue;
            }

            $card->subjects = $next;
            try {
                $card->saveQuietly();
            } catch (\Throwable $e) {
                // The card still shows the datesheet; it is only not kept.
                report($e);
            }
        }
    }

    /** "09:00:00" → "09:00"; nothing, or anything unreadable, → "". */
    private static function clock($time): string
    {
        if (!$time) {
            return '';
        }
        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** "3 Hours", "1 Hour 30 Min" — from the start to the end. */
    private static function duration($start, $end): string
    {
        if (!$start || !$end) {
            return '';
        }
        try {
            // From the start to the end: Carbon 3 counts it negative the other way round.
            $mins = (int) round(Carbon::parse($start)->diffInMinutes(Carbon::parse($end)));
        } catch (\Throwable $e) {
            return '';
        }
        if ($mins <= 0) {
            return '';
        }

        $h = intdiv($mins, 60);
        $m = $mins % 60;
        $label = $h ? $h . ($h === 1 ? ' Hour' : ' Hours') : '';

        return trim($label . ($m ? " {$m} Min" : ''));
    }
}
