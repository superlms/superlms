<?php

namespace Tests\Feature;

use App\Livewire\Admin\AddExam;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Exams page: a new exam starts published, the form has no Description, the
 * list reads name over term with Total / Passing in one column, dates on one
 * line, plain-text status and no Year; Exam Papers with an exam and a class
 * picked list every subject of the class — its paper with View / Download /
 * Edit / Delete, or Add — where Add and Edit take a PDF (up to 2 MB) straight
 * from the file picker, and a deleted paper leaves its subject in the list;
 * Exam Syllabus with an exam and a class picked lists every subject with its
 * chapters joined by dots, and its panel asks exam, class and subject.
 */
class ExamPapersBoardTest extends TestCase
{
    private int $org = 5;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['app.key' => 'base64:' . base64_encode(str_repeat('e', 32))]);
        Storage::fake('s3');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('code')->nullable();
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('exams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('exam_name'); $t->string('term')->nullable();
            $t->string('academic_year')->nullable(); $t->date('start_date')->nullable(); $t->date('end_date')->nullable();
            $t->text('description')->nullable(); $t->boolean('is_published')->default(false); $t->string('exam_type')->nullable();
            $t->integer('total_marks')->nullable(); $t->integer('passing_marks')->nullable(); $t->boolean('uses_grading_system')->default(false);
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('exam_syllabus_chapters', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('exam_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->nullable(); $t->unsignedBigInteger('subject_id'); $t->unsignedBigInteger('chapter_id'); $t->timestamps();
        });
        Schema::create('chapters', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('subject_id'); $t->string('name'); $t->integer('order')->default(1); $t->timestamps();
        });
        Schema::create('exam_papers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('exam_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->nullable(); $t->unsignedBigInteger('subject_id')->nullable(); $t->string('title');
            $t->text('description')->nullable(); $t->string('file_path')->nullable(); $t->unsignedBigInteger('uploaded_by')->nullable(); $t->timestamps();
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** A class with one section and three subjects, an exam, and a Hindi paper. */
    private function school(): array
    {
        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'NURSERY']);
        $sec = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'name' => 'A']);
        $subs = [];
        foreach (['Hindi', 'English', 'Maths'] as $name) {
            $subs[$name] = DB::table('subjects')->insertGetId(['organization_id' => $this->org, 'name' => $name]);
            DB::table('standard_subjects')->insert(['standard_id' => $std, 'subject_id' => $subs[$name]]);
        }
        $exam = DB::table('exams')->insertGetId([
            'organization_id' => $this->org, 'exam_name' => 'Half Yearly', 'term' => 'Term-1', 'academic_year' => '2026-2027',
            'exam_type' => 'written', 'total_marks' => 100, 'passing_marks' => 33, 'is_published' => true,
            'description' => 'Old note', 'start_date' => '2026-11-02', 'end_date' => '2026-11-12',
        ]);
        Storage::disk('s3')->put('admin/exam-papers/5/hindi.pdf', '%PDF-1.4');
        $paper = DB::table('exam_papers')->insertGetId([
            'organization_id' => $this->org, 'exam_id' => $exam, 'standard_id' => $std, 'section_id' => null,
            'subject_id' => $subs['Hindi'], 'title' => 'Hindi Paper', 'file_path' => 'admin/exam-papers/5/hindi.pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return compact('std', 'sec', 'subs', 'exam', 'paper');
    }

    public function test_exam_form_and_list(): void
    {
        $this->school();
        $page = Livewire::test(AddExam::class);

        $this->assertTrue($page->get('isPublished'));
        $page->call('onAddExam');
        $this->assertTrue($page->get('isPublished'));
        $html = $page->html();
        $this->assertStringNotContainsString('wire:model.defer="description"', $html);
        $this->assertStringContainsString('Publish immediately', $html);
        $this->assertStringContainsString('Total / Passing', $html);
        $this->assertStringContainsString('100 / 33', $html);
        $this->assertStringNotContainsString('>Year</th>', $html);
        $this->assertStringNotContainsString('Old note', $html);                       // description not under the name
        $this->assertMatchesRegularExpression('/Half Yearly<\/p>\s*(<!--\[if BLOCK\]><!\[endif\]-->)?\s*<p class="text-xs text-gray-400 mt-0.5">Term-1<\/p>/', $html);
        $this->assertStringContainsString('02 Nov 2026 → 12 Nov 2026', $html);         // the dates on one line
        $this->assertStringNotContainsString('rounded-full uppercase tracking-wide', $html); // no status chip
        $this->assertMatchesRegularExpression('/class="text-sm text-gray-700 hover:text-gray-900">\s*Published\s*<\/button>/', $html);
        $this->assertStringNotContainsString('wire:model.live="filterAcademicYear"', $html);   // no Year filter
        $this->assertMatchesRegularExpression('/wire:click="onViewExam\(\d+\)" title="View"\s*class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"/', $html);
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-500', $html);
        $page->set('search', 'Half');
        $this->assertMatchesRegularExpression('/wire:click="clearExamFilters"\s*class="inline-flex/', $page->html()); // Clear beside the fields
        $page->set('search', '');

        // Saving an exam left ticked publishes it; editing keeps its description.
        $page->set('term', 'Term-2')->set('examName', 'Annual')->set('examType', 'written')
            ->set('totalMarks', 80)->set('passingMarks', 27)->call('onSave');
        $this->assertSame(1, (int) DB::table('exams')->where('exam_name', 'Annual')->value('is_published'));
        $old = DB::table('exams')->where('exam_name', 'Half Yearly')->value('id');
        $page->call('onEditExam', $old)->set('examName', 'Half Yearly Exam')->call('onSave');
        $this->assertSame('Old note', DB::table('exams')->where('id', $old)->value('description'));
    }

    public function test_subjects_list_add_and_delete_keeps_the_subject(): void
    {
        $s = $this->school();

        $page = Livewire::test(AddExam::class)->call('setTab', 'papers');
        $html = $page->html();
        $this->assertStringNotContainsString('<span class="text-gray-300">→</span>', explode('Syllabus tab', $html)[0]);
        $this->assertStringContainsString('Hindi Paper', $html);                       // no exam/class: the papers table, as before
        $this->assertStringNotContainsString('wire:click="openPaperModal"', $html);    // no Upload Paper in the header
        $this->assertMatchesRegularExpression('/wire:click="downloadPaper\(\d+\)" title="Download"\s*class="p-1.5 text-emerald-600/', $html);

        $page->set('filterPaperExam', (string) $s['exam'])->set('filterPaperStandard', (string) $s['std'])
            ->set('filterPaperSection', (string) $s['sec']);                            // the class's only section (picked by itself on the page)
        $html = $page->html();
        foreach (['Hindi', 'English', 'Maths'] as $name) {
            $this->assertStringContainsString('<span class="text-sm font-semibold text-gray-900">' . $name . '</span>', $html);
        }
        $this->assertStringContainsString('Hindi Paper', $html);                       // a whole-class paper shows under the section
        $this->assertSame(2, substr_count($html, 'Add Paper'));                        // English and Maths
        $this->assertStringContainsString('wire:click="viewPaper(' . $s['paper'] . ')"', $html);
        $this->assertStringContainsString('wire:click="downloadPaper(' . $s['paper'] . ')"', $html);
        $this->assertStringContainsString('x-on:click="pick(\'edit:' . $s['paper'] . '\')"', $html);          // Edit = the file picker
        $this->assertStringContainsString('x-on:click="pick(\'add:' . $s['subs']['Maths'] . '\')"', $html);   // Add = the file picker
        $this->assertStringContainsString('wire:click="onDeletePaper(' . $s['paper'] . ')"', $html);

        // A PDF picked from Maths' Add is saved at once for this exam, class and section.
        $page->set('quickPaperFor', 'add:' . $s['subs']['Maths'])
            ->set('quickPaperFile', UploadedFile::fake()->create('maths.pdf', 1900, 'application/pdf'));
        $maths = DB::table('exam_papers')->where('subject_id', $s['subs']['Maths'])->first();
        $this->assertNotNull($maths);
        $this->assertSame(['Maths', $s['exam'], $s['std'], $s['sec']],
            [$maths->title, (int) $maths->exam_id, (int) $maths->standard_id, (int) $maths->section_id]);
        Storage::disk('s3')->assertExists($maths->file_path);
        $this->assertSame('', $page->get('quickPaperFor'));

        // Over 2 MB, or not a PDF: nothing is saved.
        $page->set('quickPaperFor', 'add:' . $s['subs']['English'])
            ->set('quickPaperFile', UploadedFile::fake()->create('big.pdf', 2100, 'application/pdf'));
        $page->set('quickPaperFor', 'add:' . $s['subs']['English'])
            ->set('quickPaperFile', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'));
        $this->assertSame(0, DB::table('exam_papers')->where('subject_id', $s['subs']['English'])->count());

        // Edit with a new PDF: the same paper, a new file, the old one off storage.
        $page->set('quickPaperFor', 'edit:' . $maths->id)
            ->set('quickPaperFile', UploadedFile::fake()->create('maths2.pdf', 50, 'application/pdf'));
        $after = DB::table('exam_papers')->where('id', $maths->id)->first();
        $this->assertSame('Maths', $after->title);
        $this->assertNotSame($maths->file_path, $after->file_path);
        Storage::disk('s3')->assertMissing($maths->file_path);
        Storage::disk('s3')->assertExists($after->file_path);
        DB::table('exam_papers')->where('id', $maths->id)->delete();

        // The upload panel (the papers table's Edit) still works, now up to 2 MB.
        $this->assertStringContainsString('(max 2 MB)', $page->call('openPaperModal')->html());
        $page->call('closePaperModal');

        // The earlier helper still opens the panel on a subject.
        $page->call('openPaperModalFor', (string) $s['subs']['Maths']);
        $this->assertTrue($page->get('showPaperModal'));
        $this->assertSame((string) $s['subs']['Maths'], $page->get('paperSubject'));
        $page->call('closePaperModal');

        // Delete: the paper goes, Hindi stays in the list with its Add.
        $page->call('onDeletePaper', $s['paper'])->call('confirmDeletePaper');
        $this->assertSame(0, DB::table('exam_papers')->count());
        Storage::disk('s3')->assertMissing('admin/exam-papers/5/hindi.pdf');
        $html = $page->html();
        $this->assertStringContainsString('<span class="text-sm font-semibold text-gray-900">Hindi</span>', $html);
        $this->assertSame(3, substr_count($html, 'Add Paper'));

        // The Subject box narrows the list to one subject.
        $page->set('filterPaperSubject', (string) $s['subs']['English']);
        $html = $page->html();
        $this->assertStringContainsString('>English</span>', $html);
        $this->assertStringNotContainsString('>Maths</span>', $html);
    }

    public function test_syllabus_subjects_list_and_panel(): void
    {
        $s = $this->school();
        $ch = [];
        foreach (['Varnmala' => 1, 'Matra' => 2, 'Kavita' => 3] as $name => $order) {
            $ch[$name] = DB::table('chapters')->insertGetId(['organization_id' => $this->org, 'standard_id' => $s['std'],
                'subject_id' => $s['subs']['Hindi'], 'name' => $name, 'order' => $order]);
        }
        foreach (['Matra', 'Varnmala'] as $name) {
            DB::table('exam_syllabus_chapters')->insert(['organization_id' => $this->org, 'exam_id' => $s['exam'],
                'standard_id' => $s['std'], 'section_id' => $s['sec'], 'subject_id' => $s['subs']['Hindi'], 'chapter_id' => $ch[$name]]);
        }

        $page = Livewire::test(AddExam::class)->call('setTab', 'syllabus');
        $html = $page->html();
        $bar = explode('View:', $html)[1];
        $this->assertStringNotContainsString('<span class="text-gray-300">→</span>', explode('BODY', $bar)[0]);
        // Nothing listed until filtered.
        $this->assertStringContainsString('Pick an exam and a class', $html);
        $this->assertStringNotContainsString('Varnmala', $html);
        $this->assertStringNotContainsString('wire:click="onViewSyllabus(', $html);
        $this->assertStringContainsString('Now pick a class', $page->set('syllabusFilterExam', (string) $s['exam'])->html());

        $page->set('syllabusFilterExam', (string) $s['exam'])->set('syllabusFilterStandard', (string) $s['std']);
        $html = $page->html();
        $this->assertStringContainsString('Varnmala · Matra', $html);                   // chapter order, joined by dots
        $this->assertSame(2, substr_count($html, 'Not added yet'));                    // English, Maths
        $this->assertStringContainsString('wire:click="openSyllabusFor(' . $s['subs']['Maths'] . ')"', $html);
        $this->assertStringContainsString('wire:click="onEditSyllabus(' . $s['exam'] . ', ' . $s['std'] . ', ' . $s['subs']['Hindi'] . ', ' . $s['sec'] . ')"', $html);

        // Add from Hindi's row in another exam: exam, class and subject set; the
        // one section taken by itself (no Section box); chapters added elsewhere
        // say so in plain text with the exam's name.
        $other = DB::table('exams')->insertGetId(['organization_id' => $this->org, 'exam_name' => 'Annual', 'term' => 'Term-2', 'is_published' => true]);
        $page->set('syllabusFilterExam', (string) $other)->set('syllabusFilterStandard', (string) $s['std'])
            ->call('openSyllabusFor', $s['subs']['Hindi']);
        $this->assertSame([(string) $other, (string) $s['std'], (string) $s['sec'], (string) $s['subs']['Hindi']],
            [$page->get('sylModalExamId'), $page->get('sylModalStandardId'), $page->get('sylModalSectionId'), $page->get('sylModalSubjectId')]);
        $html = $page->html();
        $this->assertStringNotContainsString('wire:model.live="sylModalSectionId"', $html);
        $this->assertSame(2, substr_count($html, 'Added · Half Yearly'));
        $this->assertStringContainsString('max-w-3xl', $html);

        // A fresh Add: picking the class brings its subjects at once.
        $page->call('closeSyllabusModal')->call('onAddSyllabus')->set('sylModalStandardId', (string) $s['std']);
        $this->assertSame((string) $s['sec'], $page->get('sylModalSectionId'));
        $this->assertCount(3, $page->get('sylModalSubjects'));
    }
}
