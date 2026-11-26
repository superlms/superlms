<?php

namespace Tests\Feature;

use App\Livewire\Admin\Documents as AdminDocuments;
use App\Livewire\SuperAdmin\Documents as SuperAdminDocuments;
use App\Models\Admin\AdminDocument;
use App\Models\SuperAdmin\SuperAdminDocument;
use App\Models\SuperAdmin\SuperAdminDocumentFile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Documents (2026-11-26): a document is a title, then a
 * description, then one file or several — each file with a name of its own —
 * and it is ONE row in the list, in the Super Admin's and in the schools'
 * Documents; View lists every file in it. The schools' admins get one bell
 * notification for it.
 *
 * A school admin's own Add Doc still makes one document per file picked.
 */
class DocumentsAddManyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('d', 32))]);
        Storage::fake('s3');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->text('permissions')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->string('type'); $t->string('notifiable_type'); $t->unsignedBigInteger('notifiable_id');
            $t->text('data'); $t->timestamp('read_at')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_07_20_000001_create_super_admin_documents_tables.php'))->up();
        (require base_path('database/migrations/2026_09_02_000001_create_admin_documents_table.php'))->up();
        (require base_path('database/migrations/2026_11_26_130000_create_super_admin_document_files_table.php'))->up();

        DB::table('organizations')->insert([['id' => 1, 'name' => 'Adarsh Public School'], ['id' => 2, 'name' => 'Sunrise Academy']]);
        DB::table('users')->insert([
            ['id' => 10, 'name' => 'Adarsh Admin', 'role' => 'admin', 'organization_id' => 1],
            ['id' => 11, 'name' => 'Sunrise Admin', 'role' => 'admin', 'organization_id' => 2],
        ]);
    }

    private function superAdmin(): User
    {
        return User::forceCreate(['name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin']);
    }

    private function text($page): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($page->html()));
    }

    public function test_the_super_admin_adds_one_document_with_several_named_files(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(SuperAdminDocuments::class)->call('openCreate');

        // Title first, then the description, then the files.
        $html = $page->html();
        $this->assertLessThan(strpos($html, 'wire:model="description"'), strpos($html, 'wire:model="title"'));
        $this->assertLessThan(strpos($html, 'Choose files'), strpos($html, 'wire:model="description"'));

        $page->set('title', 'Circulars')
            ->set('docPick', UploadedFile::fake()->create('summer_vacation-circular.pdf', 120, 'application/pdf'))
            ->set('docPick', UploadedFile::fake()->create('Fee Chart 2026.xlsx', 40))
            ->set('docPick', UploadedFile::fake()->image('holiday.png'))
            ->assertSet('docPick', null)
            ->assertCount('docFiles', 3)
            ->assertSet('docTitles', ['Summer vacation circular', 'Fee Chart 2026', 'Holiday'])
            ->assertSee('3 files')
            ->assertSee('all in this document; give each its name')
            ->assertSee('Send Document');

        // A name can be changed, and a file taken out before saving.
        $page->set('docTitles.1', 'Fee chart for 2026–27')
            ->call('removeDoc', 2)
            ->set('description', 'For all staff')
            ->set('audienceScope', 'selected')
            ->set('selectedOrgs', [1])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false)
            ->assertSet('docFiles', []);

        // One document, with both files in it under their names.
        $this->assertSame(1, SuperAdminDocument::count());
        $doc = SuperAdminDocument::first();
        $this->assertSame('Circulars', $doc->title);
        $this->assertSame('For all staff', $doc->description);
        $this->assertSame('summer_vacation-circular.pdf', $doc->file_name);   // its first file
        $this->assertSame([1], $doc->organizations()->pluck('organizations.id')->all());
        $files = $doc->files()->get();
        $this->assertSame(['Summer vacation circular', 'Fee chart for 2026–27'], $files->pluck('name')->all());
        $this->assertSame(['summer_vacation-circular.pdf', 'Fee Chart 2026.xlsx'], $files->pluck('file_name')->all());
        foreach ($files as $file) {
            Storage::disk('s3')->assertExists($file->file_path);
        }

        // One bell notification, to the chosen school's admin only.
        $bells = DB::table('notifications')->get();
        $this->assertCount(1, $bells);
        $this->assertSame(10, (int) $bells[0]->notifiable_id);
        $this->assertSame('New document: Circulars', json_decode($bells[0]->data, true)['title']);

        // One row in the list, the files' names under the title.
        $seen = $this->text($page);
        $this->assertSame(1, substr_count($page->html(), 'wire:key="doc-'));
        $this->assertStringContainsString('2 FILES Circulars Summer vacation circular, Fee chart for 2026–27', $seen);

        // View lists every file, each to open or download under its name.
        $page->call('view', $doc->id);
        $seen = $this->text($page);
        foreach (['Circulars', '2 files', 'For all staff', 'Sent to Adarsh Public School', 'Summer vacation circular', 'Fee chart for 2026–27', 'Fee Chart 2026.xlsx'] as $part) {
            $this->assertStringContainsString($part, $seen);
        }
        $page->call('downloadFile', $files[1]->id)->assertFileDownloaded('Fee chart for 2026–27.xlsx');

        // Search finds it by a file's name.
        $page->call('closeView')->set('search', 'Fee chart');
        $this->assertStringContainsString('Circulars', $this->text($page));
    }

    public function test_a_title_and_a_file_are_needed_and_a_big_file_is_refused(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(SuperAdminDocuments::class)->call('openCreate')
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'docFiles' => 'required']);

        $page->set('docPick', UploadedFile::fake()->create('huge.pdf', 6000))
            ->assertHasErrors('docPick')
            ->assertSee('huge.pdf is larger than 5 MB.')
            ->assertCount('docFiles', 0);

        $page->set('title', 'Papers')
            ->set('docPick', UploadedFile::fake()->create('a.pdf', 10))
            ->set('docTitles.0', '   ')
            ->call('save')
            ->assertHasErrors('docTitles.0')
            ->assertSee('Give this file a name.');

        $this->assertSame(0, SuperAdminDocument::count());
    }

    public function test_one_file_is_one_document_to_every_school(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(SuperAdminDocuments::class)
            ->call('openCreate')
            ->set('title', 'Circular')
            ->set('docPick', UploadedFile::fake()->create('circular.pdf', 50, 'application/pdf'))
            ->assertSee('Send Document')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['Circular'], SuperAdminDocument::pluck('title')->all());
        $this->assertSame(['Circular'], SuperAdminDocumentFile::pluck('name')->all());
        $this->assertSame('New document: Circular', json_decode(DB::table('notifications')->value('data'), true)['title']);
        $this->assertSame(2, DB::table('notifications')->count()); // all schools: both admins
    }

    public function test_a_file_that_does_not_reach_storage_stays_in_the_panel_and_makes_no_document(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(SuperAdminDocuments::class)->call('openCreate')
            ->set('title', 'Two files')
            ->set('docPick', UploadedFile::fake()->create('one.pdf', 10))
            ->set('docPick', UploadedFile::fake()->create('two.pdf', 10));

        // The s3 disk doesn't throw on a failed write: the file is simply not there.
        $s3 = \Mockery::mock(Storage::disk('s3'))->makePartial();
        $s3->shouldReceive('exists')->andReturnUsing(fn ($path) => !str_contains($path, 'super-admin/documents/'));
        Storage::set('s3', $s3);

        $page->call('save')
            ->assertHasErrors('docFiles')
            ->assertSee('one.pdf: the file could not be stored')
            ->assertSet('showPanel', true)
            ->assertCount('docFiles', 2);

        $this->assertSame(0, SuperAdminDocument::count());
        $this->assertSame(0, SuperAdminDocumentFile::count());
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_editing_renames_takes_out_and_adds_files(): void
    {
        $this->actingAs($this->superAdmin());
        Storage::disk('s3')->put('super-admin/documents/x.pdf', 'x');
        // A document from before files had names: it has only its own file.
        $doc = SuperAdminDocument::create(['title' => 'Old', 'file_path' => 'super-admin/documents/x.pdf', 'file_name' => 'x.pdf', 'file_size' => 1, 'audience_scope' => 'all']);

        $page = Livewire::test(SuperAdminDocuments::class)
            ->call('edit', $doc->id)
            ->assertSet('docFiles', [])
            ->assertCount('keptFiles', 1)
            ->assertSet('keptFiles.0.name', 'Old')
            ->assertSee('Add more files')
            ->set('title', 'Exam papers')
            ->set('keptFiles.0.name', 'Maths')
            ->set('docPick', UploadedFile::fake()->create('science.pdf', 20))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false);

        $doc->refresh();
        $this->assertSame('Exam papers', $doc->title);
        $this->assertSame(['Maths', 'Science'], $doc->files()->pluck('name')->all());
        $this->assertSame(1, SuperAdminDocument::count());

        // Take the first out: it goes from storage, and the document's own file follows the new first.
        $page->call('edit', $doc->id)->call('removeKeptFile', 0)->call('save')->assertHasNoErrors();
        $doc->refresh();
        $this->assertSame(['Science'], $doc->files()->pluck('name')->all());
        $this->assertSame('science.pdf', $doc->file_name);
        Storage::disk('s3')->assertMissing('super-admin/documents/x.pdf');

        // At least one file stays.
        $page->call('edit', $doc->id)->call('removeKeptFile', 0)->call('save')
            ->assertHasErrors('docFiles')->assertSee('Keep at least one file, or add one.');
        $this->assertSame(1, $doc->files()->count());

        // Delete takes every file with it.
        $key = $doc->files()->value('file_path');
        $page->call('closePanel')->call('confirmDelete', $doc->id)->call('delete');
        $this->assertSame(0, SuperAdminDocument::count());
        $this->assertSame(0, SuperAdminDocumentFile::count());
        Storage::disk('s3')->assertMissing($key);
    }

    public function test_the_migration_gives_every_old_document_its_file_by_its_title(): void
    {
        Schema::drop('super_admin_document_files');
        DB::table('super_admin_documents')->insert([
            'title' => 'Invoices', 'file_path' => 'super-admin/documents/inv.pdf', 'file_name' => 'inv.pdf',
            'file_size' => 1400, 'audience_scope' => 'all', 'created_at' => now(), 'updated_at' => now(),
        ]);
        (require base_path('database/migrations/2026_11_26_130000_create_super_admin_document_files_table.php'))->up();

        $file = SuperAdminDocumentFile::first();
        $this->assertSame('Invoices', $file->name);
        $this->assertSame('inv.pdf', $file->file_name);
        $this->assertSame(1400, $file->file_size);
        $this->assertSame(1, SuperAdminDocumentFile::count());
    }

    public function test_a_school_sees_the_document_as_one_row_and_every_file_in_it(): void
    {
        $doc = SuperAdminDocument::create(['title' => 'Circulars', 'description' => 'Read these', 'file_path' => 'a/one.pdf', 'file_name' => 'one.pdf', 'audience_scope' => 'selected']);
        $doc->organizations()->sync([1]);
        Storage::disk('s3')->put('a/one.pdf', '1');
        Storage::disk('s3')->put('a/two.pdf', '2');
        $doc->files()->createMany([
            ['name' => 'Holiday list', 'file_path' => 'a/one.pdf', 'file_name' => 'one.pdf', 'sort_order' => 0],
            ['name' => 'Fee chart', 'file_path' => 'a/two.pdf', 'file_name' => 'two.pdf', 'sort_order' => 1],
        ]);
        $two = SuperAdminDocumentFile::where('name', 'Fee chart')->value('id');

        $this->actingAs(User::find(10));
        $page = Livewire::test(AdminDocuments::class)->call('setTab', 'admin');
        $this->assertStringContainsString('2 FILES Circulars Holiday list, Fee chart', $this->text($page));
        $this->assertStringContainsString('wire:click="viewShared(' . $doc->id . ')"', $page->html());

        $page->call('viewShared', $doc->id);
        $seen = $this->text($page);
        foreach (['Circulars', '2 files', 'Read these', 'Holiday list', 'Fee chart', 'two.pdf'] as $part) {
            $this->assertStringContainsString($part, $seen);
        }
        $this->assertStringNotContainsString('Sent to', $seen);
        $page->call('downloadSharedFile', $two)->assertFileDownloaded('Fee chart.pdf');

        // Another school's admin can neither see nor download it.
        $this->actingAs(User::find(11));
        $other = Livewire::test(AdminDocuments::class)->call('setTab', 'admin')->call('viewShared', $doc->id)
            ->assertSet('viewSharedId', null);
        $other->call('downloadSharedFile', $two)->assertNoFileDownloaded();
    }

    public function test_a_school_admin_adds_several_documents_at_once(): void
    {
        $this->actingAs(User::find(10));

        Livewire::test(AdminDocuments::class)
            ->call('openCreate')
            ->set('docPick', UploadedFile::fake()->create('timetable.pdf', 30, 'application/pdf'))
            ->set('docPick', UploadedFile::fake()->create('staff_list.xlsx', 30))
            ->assertSee('Save 2 Documents')
            ->assertSee('each becomes a document of its own; give each a title')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false);

        $docs = AdminDocument::orderBy('id')->get();
        $this->assertSame(['Timetable', 'Staff list'], $docs->pluck('title')->all());
        $this->assertSame([1, 1], $docs->pluck('organization_id')->map(fn ($id) => (int) $id)->all());
    }
}
