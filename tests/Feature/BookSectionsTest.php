<?php

namespace Tests\Feature;

use App\Livewire\Admin\Book;
use App\Models\Admin\Book as BookRow;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Add Book, web panel: the sections are ticked together — picking the subject
 * ticks every section it is taught in — and the book is added for each, the
 * PDF uploaded once; none ticked is the whole class, as before. A PDF the other
 * sections' copies share stays on storage until the last of them goes. The
 * filter bar's Clear empties the filters.
 */
class BookSectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
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
        Schema::create('books', function (Blueprint $t) {
            $t->id();
            foreach (['organization_id', 'standard_id', 'section_id', 'subject_id'] as $c) {
                $t->unsignedBigInteger($c)->default(0);
            }
            $t->string('title')->nullable(); $t->string('book_logo')->nullable(); $t->string('pdf_file')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });

        DB::table('standards')->insert(['id' => 1, 'organization_id' => 4, 'name' => 'Class 5']);
        DB::table('sections')->insert([
            ['id' => 1, 'organization_id' => 4, 'standard_id' => 1, 'name' => 'A'],
            ['id' => 2, 'organization_id' => 4, 'standard_id' => 1, 'name' => 'B'],
            ['id' => 3, 'organization_id' => 4, 'standard_id' => 1, 'name' => 'C'],
        ]);
        DB::table('subjects')->insert([['id' => 6, 'organization_id' => 4, 'name' => 'Mathematics'], ['id' => 7, 'organization_id' => 4, 'name' => 'Hindi']]);
        DB::table('standard_subjects')->insert(['standard_id' => 1, 'subject_id' => 7]);
        // Maths is taught in A and B only.
        DB::table('section_subjects')->insert([
            ['standard_id' => 1, 'section_id' => 1, 'subject_id' => 6],
            ['standard_id' => 1, 'section_id' => 2, 'subject_id' => 6],
        ]);

        $admin = User::forceCreate(['name' => 'Admin', 'role' => 'admin', 'organization_id' => 4]);
        $this->actingAs($admin);
    }

    private function form(): Book
    {
        $c = new Book();
        $c->title = 'NCERT Maths';
        $c->standard_id = 1;
        $c->updatedStandardId(1);
        return $c;
    }

    public function test_picking_the_subject_ticks_the_sections_it_is_taught_in(): void
    {
        $c = $this->form();
        // The class's subjects, class-wide and by section.
        $this->assertEqualsCanonicalizing(['Mathematics', 'Hindi'], collect($c->subjects)->pluck('name')->all());

        $c->subject_id = 6;
        $c->updatedSubjectId(6);
        $this->assertSame(['1', '2'], $c->sectionIds);
    }

    public function test_the_book_is_added_for_each_section_ticked_with_one_upload(): void
    {
        $c = $this->form();
        $c->subject_id = 6;
        $c->updatedSubjectId(6);
        $c->sectionIds[] = '3';   // C ticked by hand too
        $c->pdf_file = UploadedFile::fake()->create('maths.pdf', 50, 'application/pdf');
        $c->onSave();

        $rows = BookRow::orderBy('section_id')->get();
        $this->assertSame([1, 2, 3], $rows->pluck('section_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(1, $rows->pluck('pdf_file')->unique()->count());   // one PDF for all
        $this->assertCount(1, Storage::disk('s3')->allFiles('admin/library/pdfs'));
        $this->assertSame(['NCERT Maths'], $rows->pluck('title')->unique()->values()->all());

        // The same name again for a section that has it is refused.
        $again = $this->form();
        $again->subject_id = 6;
        $again->sectionIds = ['2'];
        $again->pdf_file = UploadedFile::fake()->create('maths.pdf', 50, 'application/pdf');
        $again->onSave();
        $this->assertStringContainsString('section B', $again->getErrorBag()->first('title'));
        $this->assertSame(3, BookRow::count());
    }

    public function test_none_ticked_is_the_whole_class_as_before(): void
    {
        $c = $this->form();
        $c->subject_id = 7;
        $c->updatedSubjectId(7);   // Hindi is class-wide: nothing ticked
        $this->assertSame([], $c->sectionIds);
        $c->pdf_file = UploadedFile::fake()->create('hindi.pdf', 50, 'application/pdf');
        $c->onSave();

        $this->assertSame([0], BookRow::pluck('section_id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_a_shared_pdf_stays_until_the_last_section_s_copy_goes(): void
    {
        $c = $this->form();
        $c->subject_id = 6;
        $c->updatedSubjectId(6);
        $c->pdf_file = UploadedFile::fake()->create('maths.pdf', 50, 'application/pdf');
        $c->onSave();
        // The address as the media host gives it (the fake disk's has a /storage prefix).
        $file = Storage::disk('s3')->allFiles('admin/library/pdfs')[0];
        BookRow::query()->update(['pdf_file' => 'https://cdn.test/' . $file]);
        [$a, $b] = BookRow::orderBy('id')->get()->all();

        $page = new Book();
        $page->deleteTargetId = $a->id;
        $page->confirmDelete();
        $this->assertCount(1, Storage::disk('s3')->allFiles('admin/library/pdfs'));

        $page->deleteTargetId = $b->id;
        $page->confirmDelete();
        $this->assertCount(0, Storage::disk('s3')->allFiles('admin/library/pdfs'));
        $this->assertSame(0, BookRow::count());
    }

    public function test_clear_empties_the_filters(): void
    {
        $c = new Book();
        $c->search = 'maths';
        $c->filterStandard = 1;
        $c->filterSection = 2;
        $c->filterSubject = 6;
        $c->filterStatus = '1';
        $c->clearFilters();

        $this->assertSame(['', '', '', '', ''], [$c->search, $c->filterStandard, $c->filterSection, $c->filterSubject, $c->filterStatus]);
    }
}
