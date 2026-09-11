<?php

namespace App\Console\Commands;

use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Fill a school with a working weekly timetable so the Time Table, Arrangement
 * and teaching-load screens have real data to show.
 *
 * For every class -> section it writes a Mon-Sat grid of periods, giving each
 * period a subject and a teacher who is free at that hour, creating subjects
 * and teachers first if the school has too few of them. It then marks a couple
 * of teachers absent for a date and arranges substitutes for every period they
 * were due to take.
 *
 * Nothing is overwritten: a section that already has timetable entries is left
 * exactly as it is, so running this twice adds nothing the second time. Run it
 * deliberately - it is not scheduled.
 */
class SeedTimetableData extends Command
{
    protected $signature = 'timetable:seed
        {--org= : Only this organization id (default: every organization)}
        {--absent=2 : How many teachers to mark absent on the date}
        {--date= : Date for the absences and arrangements (default: today)}
        {--no-arrange : Mark the absences but leave their periods unarranged}
        {--dry-run : Report what would be written without touching the database}';

    protected $description = 'Seed a Mon-Sat timetable for every class-section, then mark teachers absent and arrange substitutes for their periods.';

    /** Period grid: [start, end], with the long break sitting between P3 and P4. */
    private const PERIODS = [
        ['08:00', '08:45'],
        ['08:45', '09:30'],
        ['09:30', '10:15'],
        ['10:45', '11:30'],
        ['11:30', '12:15'],
        ['12:15', '13:00'],
    ];

    /** Mon-Sat; Sunday is never a teaching day here. */
    private const DAYS = [1, 2, 3, 4, 5, 6];

    private const SUBJECTS = [
        'English'        => 'ENG',
        'Hindi'          => 'HIN',
        'Mathematics'    => 'MAT',
        'Science'        => 'SCI',
        'Social Science' => 'SST',
        'Computer'       => 'CMP',
    ];

    /** Pool the seeder draws on when a school has fewer teachers than it needs. */
    private const TEACHER_NAMES = [
        'Anjali Sharma', 'Rakesh Verma', 'Meera Nair', 'Sunil Chauhan',
        'Pooja Iyer', 'Imran Sheikh', 'Kavita Joshi', 'Deepak Rawat',
        'Neha Bansal', 'Vikram Singh', 'Shalini Gupta', 'Arun Mehta',
        'Farah Khan', 'Ritu Malhotra', 'Manoj Pandey', 'Swati Desai',
    ];

    private bool $dryRun = false;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::today();

        $absentWanted = max(0, (int) $this->option('absent'));

        $orgIds = $this->option('org')
            ? [(int) $this->option('org')]
            : Organization::pluck('id')->all();

        if (empty($orgIds)) {
            $this->warn('No organizations found.');
            return self::SUCCESS;
        }

        foreach ($orgIds as $orgId) {
            $this->seedOrganization($orgId, $date, $absentWanted);
        }

        if ($this->dryRun) {
            $this->warn('Dry run - nothing was written.');
        }

        return self::SUCCESS;
    }

    private function seedOrganization(int $orgId, Carbon $date, int $absentWanted): void
    {
        $org = Organization::find($orgId);
        $this->line('');
        $this->info('-- ' . ($org->name ?? "Organization #{$orgId}"));

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($standards->isEmpty()) {
            $this->line('  No classes set up - nothing to seed.');
            return;
        }

        // Every (class, section) that needs a weekly grid.
        $pairs = collect();
        foreach ($standards as $standard) {
            $sections = Section::where('standard_id', $standard->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            foreach ($sections as $section) {
                $pairs->push(['standard' => $standard, 'section' => $section]);
            }
        }

        if ($pairs->isEmpty()) {
            $this->line('  Classes exist but none has a section - nothing to seed.');
            return;
        }

        $subjects = $this->ensureSubjects($orgId);
        $this->mapSubjectsToSections($orgId, $pairs, $subjects);

        // Enough teachers to staff every section at once, cover the absences,
        // and still leave a spare or two free for substitution.
        $needed   = $pairs->count() + $subjects->count() + $absentWanted;
        $teachers = $this->ensureTeachers($orgId, $needed, $subjects);

        // On a dry run the roster was never topped up, so the grid below would
        // have nobody to hand periods to - report the plan and stop here.
        if ($this->dryRun && $teachers->count() < $needed) {
            $sections = $pairs->reject(fn($p) => TeacherTimeTable::where('organization_id', $orgId)
                ->where('standard_id', $p['standard']->id)
                ->where('section_id', $p['section']->id)
                ->exists())->count();

            $periods = $sections * count(self::DAYS) * count(self::PERIODS);
            $this->line("  Timetable: would create {$periods} period(s) across {$sections} section(s).");
            $this->line("  Absences: would mark {$absentWanted} teacher(s) absent on {$date->toDateString()} and arrange cover for their periods.");
            return;
        }

        if ($teachers->count() < $pairs->count()) {
            $this->warn('  Not enough teachers to staff every section at the same hour - skipping.');
            return;
        }

        $grid = $this->buildTimetable($orgId, $pairs, $subjects, $teachers);
        $this->line("  Timetable: {$grid['created']} period(s) created, {$grid['skipped']} section(s) already had one.");

        if ($absentWanted < 1) {
            return;
        }

        $absent = $this->markAbsences($orgId, $teachers, $date, $absentWanted);

        if ($absent->isEmpty()) {
            $this->line('  Nobody marked absent.');
            return;
        }

        $names = $absent->map(fn($t) => $t->user?->name ?? "#{$t->id}")->implode(', ');
        $this->line("  Absent on {$date->toDateString()}: {$names}");

        if ($this->option('no-arrange')) {
            $this->line('  Arrangements left for the admin to fill in.');
            return;
        }

        $arranged = $this->arrangeSubstitutes($orgId, $absent, $date);
        $this->line("  Arrangements: {$arranged['created']} period(s) covered"
            . ($arranged['uncovered'] ? ", {$arranged['uncovered']} left uncovered (no free teacher)" : '')
            . ($arranged['existing'] ? ", {$arranged['existing']} already arranged" : '') . '.');
    }

    // --- Lookups the timetable needs before it can be built ---------------

    /** @return \Illuminate\Support\Collection<int, Subject> */
    private function ensureSubjects(int $orgId)
    {
        $existing = Subject::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $added = 0;
        foreach (self::SUBJECTS as $name => $code) {
            if ($existing->contains(fn($s) => strcasecmp($s->name, $name) === 0)) {
                continue;
            }
            if (!$this->dryRun) {
                $existing->push(Subject::create([
                    'name'            => $name,
                    'code'            => $code,
                    'organization_id' => $orgId,
                    'is_active'       => true,
                ]));
            }
            $added++;
        }

        if ($added) {
            $this->line("  Subjects: {$added} created.");
        }

        // Keep to the seeded set where we can - a leftover one-off subject
        // would make the weekly rotation lopsided.
        $wanted = array_map('strtolower', array_keys(self::SUBJECTS));
        $set    = $existing->filter(fn($s) => in_array(strtolower($s->name), $wanted, true))->values();

        return $set->isNotEmpty() ? $set : $existing->values();
    }

    private function mapSubjectsToSections(int $orgId, $pairs, $subjects): void
    {
        if ($this->dryRun) {
            return;
        }

        foreach ($pairs as $pair) {
            foreach ($subjects as $subject) {
                SectionSubject::firstOrCreate([
                    'organization_id' => $orgId,
                    'standard_id'     => $pair['standard']->id,
                    'section_id'      => $pair['section']->id,
                    'subject_id'      => $subject->id,
                ]);
            }
        }
    }

    /**
     * Active teachers for the school, topping the roster up to $needed by
     * creating new ones. Each teacher gets a primary subject so the timetable
     * can hand them the periods they actually teach.
     *
     * @return \Illuminate\Support\Collection<int, TeacherDetail>
     */
    private function ensureTeachers(int $orgId, int $needed, $subjects)
    {
        $teachers = TeacherDetail::with('user')
            ->where('organization_id', $orgId)
            ->whereHas('user', fn($q) => $q->where('is_active', 1))
            ->orderBy('id')
            ->get();

        $missing = $needed - $teachers->count();
        if ($missing < 1) {
            return $teachers;
        }

        if ($this->dryRun) {
            $this->line("  Teachers: would create {$missing} to reach a roster of {$needed}.");
            return $teachers;
        }

        $taken = User::where('organization_id', $orgId)->pluck('name')->map('strtolower')->all();
        $pool  = array_values(array_filter(
            self::TEACHER_NAMES,
            fn($n) => !in_array(strtolower($n), $taken, true)
        ));

        $startIndex = $teachers->count();
        for ($i = 0; $i < $missing; $i++) {
            $name = $pool[$i] ?? ('Teacher ' . ($startIndex + $i + 1));
            $teachers->push($this->createTeacher($orgId, $name, $startIndex + $i, $subjects));
        }

        $this->line("  Teachers: {$missing} created (roster is now {$teachers->count()}).");

        return $teachers->values();
    }

    private function createTeacher(int $orgId, string $name, int $index, $subjects): TeacherDetail
    {
        $slug     = strtolower(str_replace(' ', '.', $name));
        $email    = $slug . '@org' . $orgId . '.school.local';
        $plain    = 'Teach@' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
        $employee = 'EMP' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

        return DB::transaction(function () use ($orgId, $name, $email, $plain, $employee, $index, $subjects) {
            $user = new User();
            $user->fill([
                'name'            => $name,
                'email'           => $email,
                'mobile_number'   => '9' . str_pad((string) (100000000 + $index), 9, '0', STR_PAD_LEFT),
                'role'            => 'teacher',
                'is_active'       => 1,
                'organization_id' => $orgId,
                'password'        => Hash::make($plain),
            ]);
            $user->rememberPlainPassword($plain);
            if (Schema::hasColumn('users', 'date_of_joining')) {
                $user->date_of_joining = Carbon::today()->subYears(2)->toDateString();
            }
            $user->save();

            $detail = TeacherDetail::create([
                'user_id'         => $user->id,
                'organization_id' => $orgId,
                'employee_id'     => $employee,
                'date_of_joining' => Carbon::today()->subYears(2)->toDateString(),
                'qualification'   => 'M.A., B.Ed.',
                'phone'           => $user->mobile_number,
            ]);

            // Primary subject, cycled through the subject list.
            if ($subjects->isNotEmpty()) {
                TeacherSubject::firstOrCreate([
                    'teacher_detail_id' => $detail->id,
                    'subject_id'        => $subjects[$index % $subjects->count()]->id,
                    'organization_id'   => $orgId,
                ]);
            }

            return $detail->load('user');
        });
    }

    // --- The weekly grid --------------------------------------------------

    /**
     * One Mon-Sat grid per section. Subjects rotate through the day and shift
     * by weekday, so a subject lands in a different period each day; teachers
     * are picked from whoever is free at that hour, preferring the one who
     * already takes that subject for that section.
     *
     * @return array{created:int, skipped:int}
     */
    private function buildTimetable(int $orgId, $pairs, $subjects, $teachers): array
    {
        $subjectCount = $subjects->count();

        // teacher id => subject id they are primarily attached to.
        $primary = [];
        foreach ($teachers->values() as $i => $teacher) {
            $primary[$teacher->id] = $subjects[$i % $subjectCount]->id;
        }

        // Who is already booked, counting entries this school had before we
        // ran: busy["{day}|{start}"] = [teacher_id => true]
        $busy = [];
        foreach (TeacherTimeTable::where('organization_id', $orgId)->get(['teacher_detail_id', 'day_of_week', 'start_time']) as $row) {
            $busy[$row->day_of_week . '|' . substr($row->start_time, 0, 5)][$row->teacher_detail_id] = true;
        }

        // (section, subject) => teacher who takes it, so a subject keeps the
        // same face all week wherever the timetable allows.
        $subjectTeacher = [];

        $created = 0;
        $skipped = 0;

        foreach ($pairs->values() as $sectionIndex => $pair) {
            $standardId = $pair['standard']->id;
            $sectionId  = $pair['section']->id;

            $alreadyHas = TeacherTimeTable::where('organization_id', $orgId)
                ->where('standard_id', $standardId)
                ->where('section_id', $sectionId)
                ->exists();

            if ($alreadyHas) {
                $skipped++;
                continue;
            }

            foreach (self::DAYS as $day) {
                foreach (self::PERIODS as $periodIndex => [$start, $end]) {
                    $subject = $subjects[($periodIndex + $day + $sectionIndex * 2) % $subjectCount];
                    $slotKey = $day . '|' . $start;

                    $teacher = $this->pickTeacher(
                        $teachers,
                        $primary,
                        $subjectTeacher["{$sectionId}|{$subject->id}"] ?? null,
                        $subject->id,
                        $busy[$slotKey] ?? []
                    );

                    if (!$teacher) {
                        continue; // every teacher is booked at this hour
                    }

                    $busy[$slotKey][$teacher->id]                 = true;
                    $subjectTeacher["{$sectionId}|{$subject->id}"] = $teacher->id;

                    if (!$this->dryRun) {
                        TeacherTimeTable::create([
                            'organization_id'   => $orgId,
                            'assigned_by'       => 0,
                            'teacher_detail_id' => $teacher->id,
                            'standard_id'       => $standardId,
                            'section_id'        => $sectionId,
                            'subject_id'        => $subject->id,
                            'day_of_week'       => $day,
                            'start_time'        => $start,
                            'end_time'          => $end,
                            'is_active'         => true,
                        ]);
                    }
                    $created++;
                }
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * The best free teacher for a period: the one already taking this subject
     * for this section, else another teacher of that subject, else anyone free.
     */
    private function pickTeacher($teachers, array $primary, ?int $preferredId, int $subjectId, array $busyHere): ?TeacherDetail
    {
        if ($preferredId && !isset($busyHere[$preferredId])) {
            $preferred = $teachers->firstWhere('id', $preferredId);
            if ($preferred) {
                return $preferred;
            }
        }

        $specialist = $teachers->first(fn($t) => !isset($busyHere[$t->id]) && ($primary[$t->id] ?? null) === $subjectId);
        if ($specialist) {
            return $specialist;
        }

        return $teachers->first(fn($t) => !isset($busyHere[$t->id]));
    }

    // --- Absences and their cover -----------------------------------------

    /**
     * Marks the whole roster's attendance for the date, picking teachers who
     * actually have periods that weekday to be the absent ones - an absence
     * with no periods behind it gives the Arrangement screen nothing to do.
     *
     * @return \Illuminate\Support\Collection<int, TeacherDetail>
     */
    private function markAbsences(int $orgId, $teachers, Carbon $date, int $wanted)
    {
        $dayOfWeek = $date->dayOfWeekIso;

        $withPeriods = TeacherTimeTable::where('organization_id', $orgId)
            ->where('day_of_week', $dayOfWeek)
            ->distinct()
            ->pluck('teacher_detail_id')
            ->all();

        if (empty($withPeriods)) {
            $this->warn("  No periods fall on {$date->format('l')} - pick a weekday with --date to see arrangements.");
        }

        $absent = $teachers
            ->filter(fn($t) => in_array($t->id, $withPeriods))
            ->take($wanted)
            ->values();

        if ($absent->isEmpty()) {
            $absent = $teachers->take($wanted)->values();
        }

        if ($this->dryRun) {
            return $absent;
        }

        $absentIds = $absent->pluck('id')->all();

        foreach ($teachers as $teacher) {
            $isAbsent = in_array($teacher->id, $absentIds, true);

            TeacherAttendance::updateOrCreate(
                [
                    'teacher_detail_id' => $teacher->id,
                    'organization_id'   => $orgId,
                    'attendance_date'   => $date->toDateString(),
                ],
                [
                    'status'  => $isAbsent ? 0 : 1,
                    'remarks' => $isAbsent ? 'On leave' : null,
                ]
            );
        }

        return $absent;
    }

    /**
     * Gives every period an absent teacher was due to take to a colleague who
     * is free at that hour - the same availability rules the Arrangement screen
     * applies when an admin assigns cover by hand.
     *
     * @return array{created:int, uncovered:int, existing:int}
     */
    private function arrangeSubstitutes(int $orgId, $absent, Carbon $date): array
    {
        $dayOfWeek = $date->dayOfWeekIso;
        $absentIds = $absent->pluck('id')->all();

        $slots = TeacherTimeTable::where('organization_id', $orgId)
            ->where('day_of_week', $dayOfWeek)
            ->whereIn('teacher_detail_id', $absentIds)
            ->orderBy('start_time')
            ->get();

        $candidates = TeacherDetail::with('user')
            ->where('organization_id', $orgId)
            ->whereHas('user', fn($q) => $q->where('is_active', 1))
            ->whereNotIn('id', $absentIds)
            ->orderBy('id')
            ->get();

        // Everything that makes a candidate unavailable at a given hour: their
        // own periods that day, plus cover they have already been given.
        $ownPeriods = TeacherTimeTable::where('organization_id', $orgId)
            ->where('day_of_week', $dayOfWeek)
            ->whereIn('teacher_detail_id', $candidates->pluck('id'))
            ->get(['teacher_detail_id', 'start_time', 'end_time'])
            ->groupBy('teacher_detail_id');

        $existingArrangements = TeacherArrangement::with('timetable:id,start_time,end_time')
            ->where('organization_id', $orgId)
            ->whereDate('date', $date->toDateString())
            ->get();

        $covering = []; // teacher id => [[start, end], ...]
        foreach ($existingArrangements as $arrangement) {
            if ($arrangement->timetable) {
                $covering[$arrangement->substitute_teacher_id][] = [
                    $arrangement->timetable->start_time,
                    $arrangement->timetable->end_time,
                ];
            }
        }

        $arrangedSlotIds = $existingArrangements->pluck('teacher_time_table_id')->flip();

        $created   = 0;
        $uncovered = 0;
        $existing  = 0;

        $adminId = User::where('organization_id', $orgId)->where('role', 'admin')->value('id');

        foreach ($slots as $slot) {
            if ($arrangedSlotIds->has($slot->id)) {
                $existing++;
                continue;
            }

            $free = $candidates->filter(function ($candidate) use ($slot, $ownPeriods, $covering) {
                $clashesOwn = $ownPeriods->get($candidate->id, collect())
                    ->first(fn($p) => $p->start_time < $slot->end_time && $p->end_time > $slot->start_time);
                if ($clashesOwn) {
                    return false;
                }

                foreach ($covering[$candidate->id] ?? [] as [$start, $end]) {
                    if ($start < $slot->end_time && $end > $slot->start_time) {
                        return false;
                    }
                }

                return true;
            });

            // Spread the cover around: the free teacher with the lightest day
            // so far takes it, rather than the first one on the roster picking
            // up every period.
            $substitute = $free->sortBy(fn($c) => $ownPeriods->get($c->id, collect())->count()
                + count($covering[$c->id] ?? []))->first();

            if (!$substitute) {
                $uncovered++;
                continue;
            }

            $covering[$substitute->id][] = [$slot->start_time, $slot->end_time];

            if (!$this->dryRun) {
                TeacherArrangement::create([
                    'original_teacher_id'   => $slot->teacher_detail_id,
                    'substitute_teacher_id' => $substitute->id,
                    'teacher_time_table_id' => $slot->id,
                    'date'                  => $date->toDateString(),
                    'reason'                => 'Teacher on leave',
                    'arranged_by'           => $adminId ?? 0,
                    'organization_id'       => $orgId,
                ]);
            }
            $created++;
        }

        return ['created' => $created, 'uncovered' => $uncovered, 'existing' => $existing];
    }
}
