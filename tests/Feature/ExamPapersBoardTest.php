<?php

namespace Tests\Feature;

use App\Livewire\Admin\AddExam;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Exams page: a new exam starts published, the form has no Description, the
 * list reads name over term with Total / Passing in one column and no Year;
 * Exam Papers with an exam and a class picked list every subject of the
 * class — its paper with View / Download / Edit / Delete, or Add — and a
 * deleted paper leaves its subject in the list.
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
            'description' => 'Old note',
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
        $this->assertStringContainsString('wire:click="openEditPaperModal(' . $s['paper'] . ')"', $html);
        $this->assertStringContainsString('wire:click="onDeletePaper(' . $s['paper'] . ')"', $html);

        // Add for Maths opens the form on this exam, class, section and subject.
        $page->call('openPaperModalFor', (string) $s['subs']['Maths']);
        $this->assertTrue($page->get('showPaperModal'));
        $this->assertSame((string) $s['exam'], $page->get('paperExam'));
        $this->assertSame((string) $s['std'], $page->get('paperStandard'));
        $this->assertSame((string) $s['sec'], $page->get('paperSection'));
        $this->assertSame((string) $s['subs']['Maths'], $page->get('paperSubject'));
        $this->assertSame('Maths', $page->get('paperTitle'));
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
}
