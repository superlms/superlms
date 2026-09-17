<?php

namespace App\Console\Commands;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamSyllabusChapter;
use App\Models\Organization;
use App\Models\Student\Chapter;
use App\Models\Student\StudentDetail;
use App\Models\Student\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give a school's published exams a syllabus where they have none.
 *
 * The app's Exam Syllabus opens an exam, then a subject, then the chapters that
 * exam covers — read from exam_syllabus_chapters, which the admin panel fills
 * exam by exam. A school that never did shows nothing there.
 *
 * For every published exam without a single syllabus row, each class, section
 * and subject the school teaches (its section and class subjects, the
 * timetable and teachers' own subjects) gets a share of its chapters: a unit
 * test a small run of them, a mid term the first half, an end term all of
 * them — Term-2 exams from the second half on. Chapters are the school's own;
 * only a class, section and subject with none is given a few ordinary ones,
 * tagged `[seed]` in their description.
 *
 * An exam with syllabus rows is shown only to the classes in them, so a school
 * where some class with students would be left out is skipped entirely rather
 * than having exams disappear for that class. Exams that already have a
 * syllabus are never touched.
 */
class SeedExamSyllabus extends Command
{
    protected $signature = 'lms:seed-exam-syllabus
                            {--org= : Only this organization id}
                            {--demo : The demo school ("The Demo School")}';

    protected $description = 'Give published exams without a syllabus one, from the school\'s own chapters';

    /** Marks the chapters this command wrote. */
    public const SEED_TAG = '[seed]';

    public const DEMO_SCHOOL = 'the demo school';

    /** Chapters for a subject with none, by the subject's name. */
    private const SAMPLE_CHAPTERS = [
        'english' => [
            ['Reading Comprehension', ['Unseen passage', 'Vocabulary in context', 'Answering questions']],
            ['Grammar', ['Nouns and pronouns', 'Tenses', 'Articles and prepositions']],
            ['Writing Skills', ['Letter writing', 'Paragraph writing', 'Notice writing']],
            ['Literature', ['Prose lessons', 'Poems', 'Character sketch']],
        ],
        'hindi' => [
            ['Gadya Khand', ['Path pathan', 'Shabdarth', 'Prashn uttar']],
            ['Padya Khand', ['Kavita path', 'Bhavarth', 'Kavi parichay']],
            ['Vyakaran', ['Sangya', 'Sarvanam', 'Kriya']],
            ['Rachna', ['Patra lekhan', 'Anuchchhed lekhan', 'Kahani lekhan']],
        ],
        'mathematics' => [
            ['Numbers', ['Place value', 'Comparing numbers', 'Roman numerals']],
            ['Addition and Subtraction', ['Carrying over', 'Borrowing', 'Word problems']],
            ['Multiplication and Division', ['Tables', 'Long multiplication', 'Division with remainder']],
            ['Geometry', ['Lines and angles', 'Shapes', 'Perimeter']],
            ['Measurement', ['Length', 'Weight', 'Time and money']],
        ],
        'science' => [
            ['Living and Non-living Things', ['Features of living things', 'Plants', 'Animals']],
            ['Food and Health', ['Nutrients', 'Balanced diet', 'Hygiene']],
            ['Materials', ['Solids, liquids and gases', 'Properties of materials', 'Water']],
            ['Force and Energy', ['Push and pull', 'Sources of energy', 'Simple machines']],
        ],
        'social science' => [
            ['Our Earth', ['Continents and oceans', 'Maps and globes', 'Landforms']],
            ['Our Country', ['States and capitals', 'Rivers', 'Climate']],
            ['History', ['Early humans', 'Ancient civilisations', 'Our freedom struggle']],
            ['Civics', ['Local government', 'Rights and duties', 'Our constitution']],
        ],
        'computer' => [
            ['Know Your Computer', ['Parts of a computer', 'Input and output devices', 'Uses of computers']],
            ['Using the Keyboard and Mouse', ['Keys and their uses', 'Mouse actions', 'Typing practice']],
            ['Paint and Drawing', ['Tools', 'Shapes and colours', 'Saving a drawing']],
            ['Word Processing', ['Typing a document', 'Formatting text', 'Saving and printing']],
        ],
        'general knowledge' => [
            ['Our World', ['Countries and capitals', 'Famous places', 'Flags']],
            ['Science Around Us', ['Inventions', 'Space', 'Human body']],
            ['Sports and Games', ['Indian sports', 'Olympics', 'Famous players']],
            ['Current Affairs', ['National news', 'World news', 'Awards']],
        ],
        'environmental studies' => [
            ['My Family and Me', ['My family', 'My body', 'Good habits']],
            ['Plants Around Us', ['Parts of a plant', 'Uses of plants', 'Growing plants']],
            ['Animals Around Us', ['Pet animals', 'Wild animals', 'Animal homes']],
            ['Our Surroundings', ['Water', 'Air', 'Keeping clean']],
        ],
        'drawing' => [
            ['Lines and Shapes', ['Straight and curved lines', 'Basic shapes', 'Patterns']],
            ['Colours', ['Primary colours', 'Mixing colours', 'Colouring neatly']],
            ['Nature Drawing', ['Trees', 'Flowers', 'Landscape']],
        ],
    ];

    /** Names a subject goes by, pointing at a key above. */
    private const SUBJECT_ALIASES = [
        'maths' => 'mathematics', 'math' => 'mathematics',
        'sst' => 'social science', 'social studies' => 'social science',
        'computers' => 'computer', 'computer science' => 'computer',
        'gk' => 'general knowledge',
        'evs' => 'environmental studies',
        'art' => 'drawing', 'art and craft' => 'drawing',
    ];

    private const GENERIC_CHAPTERS = [
        ['Unit 1: Introduction', ['Basic concepts', 'Key words', 'Practice questions']],
        ['Unit 2: Building Up', ['Main ideas', 'Examples', 'Exercises']],
        ['Unit 3: Going Further', ['New concepts', 'Activities', 'Review']],
        ['Unit 4: Applying It', ['Problem solving', 'Projects', 'Revision']],
    ];

    public function handle(): int
    {
        if (!Schema::hasTable('exam_syllabus_chapters') || !Schema::hasTable('chapters')) {
            $this->warn('Syllabus tables are missing — nothing to do.');
            return self::SUCCESS;
        }

        if ($this->option('org')) {
            $orgIds = [(int) $this->option('org')];
        } elseif ($this->option('demo')) {
            $orgIds = Organization::whereRaw('LOWER(TRIM(name)) = ?', [self::DEMO_SCHOOL])->pluck('id')->all();
            if (!$orgIds) {
                $this->line('No school named "The Demo School" — nothing to do.');
            }
        } else {
            $this->error('Pass --org=ID or --demo.');
            return self::FAILURE;
        }

        foreach ($orgIds as $orgId) {
            $this->seedOrganization((int) $orgId);
        }

        return self::SUCCESS;
    }

    private function seedOrganization(int $orgId): void
    {
        $org = Organization::find($orgId);
        if (!$org) {
            $this->warn("Organization {$orgId} not found — skipped.");
            return;
        }
        $label = "[{$orgId}] {$org->name}";

        $exams = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderBy('id')
            ->get();

        $withSyllabus = ExamSyllabusChapter::whereIn('exam_id', $exams->pluck('id'))->distinct()->pluck('exam_id');
        $bare = $exams->reject(fn($e) => $withSyllabus->contains($e->id))->values();

        if ($bare->isEmpty()) {
            $this->line("{$label}: every published exam already has a syllabus — skipped.");
            return;
        }

        $places = $this->places($orgId);
        if ($places->isEmpty()) {
            $this->line("{$label}: no classes with subjects — skipped.");
            return;
        }

        // Nobody may lose an exam: once it has syllabus rows, a class without any
        // no longer sees it.
        $uncovered = StudentDetail::where('organization_id', $orgId)
            ->where('standard_id', '>', 0)
            ->get(['standard_id', 'section_id'])
            ->unique(fn($s) => $s->standard_id . '-' . (int) $s->section_id)
            // A student without a section reads the whole class's syllabus.
            ->reject(fn($s) => $places->contains(fn($p) => $p['standard_id'] === (int) $s->standard_id
                && (!$s->section_id || $p['section_id'] === 0 || $p['section_id'] === (int) $s->section_id)));
        if ($uncovered->isNotEmpty()) {
            $this->warn("{$label}: " . $uncovered->count() . ' class(es) with students have no subjects — skipped so no exam disappears for them.');
            return;
        }

        DB::transaction(function () use ($orgId, $places, $bare, $exams, $label) {
            $created = 0;
            $chaptersAt = [];
            foreach ($places as $key => $place) {
                [$chaptersAt[$key], $made] = $this->chaptersFor($orgId, $place);
                $created += $made;
            }

            $now = now();
            $linked = 0;
            foreach ($bare as $exam) {
                $rows = [];
                foreach ($places as $key => $place) {
                    foreach ($this->pick($chaptersAt[$key], $exam, $exams) as $chapter) {
                        $rows[] = [
                            'organization_id' => $orgId,
                            'exam_id'         => $exam->id,
                            'standard_id'     => $place['standard_id'],
                            'subject_id'      => $place['subject_id'],
                            'section_id'      => $chapter->section_id ?: null,
                            'chapter_id'      => $chapter->id,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ];
                    }
                }
                foreach (array_chunk($rows, 500) as $chunk) {
                    $linked += DB::table('exam_syllabus_chapters')->insertOrIgnore($chunk);
                }
            }

            $this->info("{$label}: {$bare->count()} exam(s) given a syllabus — {$linked} chapter link(s), {$created} chapter(s) created.");
        });
    }

    /**
     * Each class, section (0: the whole class) and subject the school teaches.
     *
     * @return Collection<string, array{standard_id:int, section_id:int, subject_id:int}>
     */
    private function places(int $orgId): Collection
    {
        $standards = DB::table('standards')->where('organization_id', $orgId)->pluck('id')->map(fn($id) => (int) $id);
        $subjects  = DB::table('subjects')->where('organization_id', $orgId)->pluck('id')->map(fn($id) => (int) $id);

        $rows = collect()
            ->merge(DB::table('section_subjects')->where('organization_id', $orgId)
                ->get(['standard_id', 'section_id', 'subject_id']))
            ->merge(DB::table('teacher_time_tables')->where('organization_id', $orgId)
                ->get(['standard_id', 'section_id', 'subject_id']));

        if (Schema::hasColumn('teacher_subjects', 'standard_id')) {
            $cols = Schema::hasColumn('teacher_subjects', 'section_id')
                ? ['standard_id', 'section_id', 'subject_id']
                : ['standard_id', 'subject_id'];
            $rows = $rows->merge(DB::table('teacher_subjects')->where('organization_id', $orgId)
                ->whereNotNull('standard_id')->get($cols));
        }

        // A class that lists its subjects only for the whole class.
        $withSections = $rows->pluck('standard_id')->map(fn($id) => (int) $id)->unique();
        $rows = $rows->merge(DB::table('standard_subjects')->where('organization_id', $orgId)
            ->get(['standard_id', 'subject_id'])
            ->reject(fn($r) => $withSections->contains((int) $r->standard_id)));

        $places = $rows
            ->map(fn($r) => [
                'standard_id' => (int) $r->standard_id,
                'section_id'  => (int) ($r->section_id ?? 0),
                'subject_id'  => (int) $r->subject_id,
            ])
            ->filter(fn($p) => $standards->contains($p['standard_id']) && $subjects->contains($p['subject_id']))
            ->keyBy(fn($p) => $p['standard_id'] . '-' . $p['section_id'] . '-' . $p['subject_id']);

        // Where a subject is taught section by section, the sections carry it.
        return $places->reject(fn($p) => $p['section_id'] === 0 && $places->contains(
            fn($q) => $q['section_id'] !== 0 && $q['standard_id'] === $p['standard_id'] && $q['subject_id'] === $p['subject_id']
        ));
    }

    /**
     * The chapters a class, section and subject reads, in order — the
     * school's own, or a few ordinary ones written for it when it has none.
     *
     * @return array{0: Collection, 1: int} the chapters, and how many were created
     */
    private function chaptersFor(int $orgId, array $place): array
    {
        $query = Chapter::where('organization_id', $orgId)
            ->where('standard_id', $place['standard_id'])
            ->where('subject_id', $place['subject_id']);

        if ($place['section_id']) {
            $query->where(fn($q) => $q->where('section_id', $place['section_id'])
                ->orWhere('section_id', 0)
                ->orWhereNull('section_id'));
        }

        $chapters = $query->orderBy('order')->orderBy('id')->get(['id', 'section_id', 'order', 'name']);
        if ($chapters->isNotEmpty()) {
            return [$chapters, 0];
        }

        $subjectName = strtolower(trim((string) DB::table('subjects')->where('id', $place['subject_id'])->value('name')));
        $key = self::SUBJECT_ALIASES[$subjectName] ?? $subjectName;
        $samples = self::SAMPLE_CHAPTERS[$key] ?? self::GENERIC_CHAPTERS;

        $made = collect();
        foreach ($samples as $i => [$name, $topics]) {
            $chapter = Chapter::create([
                'organization_id' => $orgId,
                'standard_id'     => $place['standard_id'],
                'section_id'      => $place['section_id'],
                'subject_id'      => $place['subject_id'],
                'name'            => $name,
                'description'     => self::SEED_TAG . ' Sample chapter for the exam syllabus.',
                'order'           => $i + 1,
                'is_published'    => true,
            ]);
            foreach ($topics as $j => $topic) {
                Topic::create([
                    'organization_id' => $orgId,
                    'chapter_id'      => $chapter->id,
                    'topic_name'      => $topic,
                    'order'           => $j + 1,
                ]);
            }
            $made->push($chapter);
        }

        return [$made, $made->count()];
    }

    /**
     * The share of a subject's chapters an exam covers: all of them for an end
     * term, a half for a mid term, a short run for a unit test — Term-2 from
     * the second half on — so consecutive tests move through the book.
     */
    private function pick(Collection $chapters, Exam $exam, Collection $allExams): Collection
    {
        $n = $chapters->count();
        if ($n === 0) {
            return collect();
        }

        $kind  = $this->kindOf($exam);
        $term2 = $this->isTerm2($exam);
        $half  = (int) ceil($n / 2);

        if ($kind === 'full') {
            return $chapters;
        }

        $pool = $term2 ? $chapters->slice($half)->values() : $chapters->slice(0, $half)->values();
        if ($pool->isEmpty()) {
            $pool = $chapters;
        }

        if ($kind === 'half') {
            return $pool;
        }

        // The how-manieth unit test of its term this is.
        $index = $allExams
            ->filter(fn($e) => $this->kindOf($e) === 'unit' && $this->isTerm2($e) === $term2)
            ->values()
            ->search(fn($e) => $e->id === $exam->id);
        $size   = max(1, (int) ceil($pool->count() / 2));
        $window = $pool->slice(((int) $index) * $size, $size)->values();

        return $window->isNotEmpty() ? $window : $pool->slice(-$size)->values();
    }

    private function kindOf(Exam $exam): string
    {
        $text = strtolower($exam->exam_name . ' ' . $exam->exam_type);

        if (preg_match('/unit|periodic|class test|weekly|monthly|cycle/', $text)) {
            return 'unit';
        }
        if (preg_match('/end|final|annual|pre-?board|term[\s-]*2/', $text)) {
            return 'full';
        }

        return 'half';
    }

    private function isTerm2(Exam $exam): bool
    {
        if ($exam->term === 'Term-2') return true;
        if ($exam->term === 'Term-1') return false;

        return (bool) preg_match('/term[\s-]*2|end|final|annual/', strtolower((string) $exam->exam_name));
    }
}
