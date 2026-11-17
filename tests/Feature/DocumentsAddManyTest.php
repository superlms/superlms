<?php

namespace Tests\Feature;

use App\Livewire\Admin\Documents as AdminDocuments;
use App\Livewire\SuperAdmin\Documents as SuperAdminDocuments;
use App\Models\Admin\AdminDocument;
use App\Models\SuperAdmin\SuperAdminDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Add Doc takes one document or several at once — in the Super Admin's
 * Documents and in a school admin's: each file picked becomes a document of
 * its own, titled from its file name unless the title is changed, all with
 * the same description (and, from the Super Admin, the same schools — whose
 * admins get one bell notification for the lot).
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

    public function test_the_super_admin_adds_several_documents_at_once(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(SuperAdminDocuments::class)
            ->call('openCreate')
            ->set('docPick', UploadedFile::fake()->create('summer_vacation-circular.pdf', 120, 'application/pdf'))
            ->set('docPick', UploadedFile::fake()->create('Fee Chart 2026.xlsx', 40))
            ->set('docPick', UploadedFile::fake()->image('holiday.png'))
            ->assertSet('docPick', null)
            ->assertCount('docFiles', 3)
            ->assertSet('docTitles', ['Summer vacation circular', 'Fee Chart 2026', 'Holiday'])
            ->assertSee('3 files')
            ->assertSee('Send 3 Documents');

        // A title can be changed, and a file taken out before saving.
        $page->set('docTitles.1', 'Fee chart for 2026–27')
            ->call('removeDoc', 2)
            ->assertCount('docFiles', 1 + 1)
            ->set('description', 'For all staff')
            ->set('audienceScope', 'selected')
            ->set('selectedOrgs', [1])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false)
            ->assertSet('docFiles', []);

        $docs = SuperAdminDocument::orderBy('id')->get();
        $this->assertSame(['Summer vacation circular', 'Fee chart for 2026–27'], $docs->pluck('title')->all());
        $this->assertSame(['summer_vacation-circular.pdf', 'Fee Chart 2026.xlsx'], $docs->pluck('file_name')->all());
        $this->assertSame(['For all staff', 'For all staff'], $docs->pluck('description')->all());
        foreach ($docs as $doc) {
            $this->assertSame([1], $doc->organizations()->pluck('organizations.id')->all());
            Storage::disk('s3')->assertExists($doc->file_path);
        }

        // One bell notification for the lot, to the chosen school's admin only.
        $bells = DB::table('notifications')->get();
        $this->assertCount(1, $bells);
        $this->assertSame(10, (int) $bells[0]->notifiable_id);
        $this->assertStringStartsWith('2 new documents: Summer vacation circular, Fee chart', json_decode($bells[0]->data, true)['title']);
    }

    public function test_one_document_still_works_as_before(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(SuperAdminDocuments::class)
            ->call('openCreate')
            ->set('docPick', UploadedFile::fake()->create('circular.pdf', 50, 'application/pdf'))
            ->assertSee('Send Document')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['Circular'], SuperAdminDocument::pluck('title')->all());
        $this->assertSame('New document: Circular', json_decode(DB::table('notifications')->value('data'), true)['title']);
        $this->assertSame(2, DB::table('notifications')->count()); // all schools: both admins
    }

    public function test_nothing_picked_a_blank_title_or_a_big_file_is_refused(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(SuperAdminDocuments::class)->call('openCreate')
            ->call('save')
            ->assertHasErrors(['docFiles' => 'required']);

        $page->set('docPick', UploadedFile::fake()->create('huge.pdf', 6000))
            ->assertHasErrors('docPick')
            ->assertSee('huge.pdf is larger than 5 MB.')
            ->assertCount('docFiles', 0);

        $page->set('docPick', UploadedFile::fake()->create('a.pdf', 10))
            ->set('docTitles.0', '   ')
            ->call('save')
            ->assertHasErrors('docTitles.0');

        $this->assertSame(0, SuperAdminDocument::count());
    }

    public function test_a_file_that_does_not_reach_storage_stays_in_the_panel_and_makes_no_document(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(SuperAdminDocuments::class)->call('openCreate')
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
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_editing_a_document_keeps_one_title_and_file(): void
    {
        $this->actingAs($this->superAdmin());
        $doc = SuperAdminDocument::create(['title' => 'Old', 'file_path' => 'super-admin/documents/x.pdf', 'file_name' => 'x.pdf', 'audience_scope' => 'all']);

        Livewire::test(SuperAdminDocuments::class)
            ->call('edit', $doc->id)
            ->assertSet('docFiles', [])
            ->set('title', 'New title')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('New title', $doc->fresh()->title);
        $this->assertSame(1, SuperAdminDocument::count());
    }

    public function test_a_school_admin_adds_several_documents_at_once(): void
    {
        $this->actingAs(User::find(10));

        Livewire::test(AdminDocuments::class)
            ->call('openCreate')
            ->set('docPick', UploadedFile::fake()->create('timetable.pdf', 30, 'application/pdf'))
            ->set('docPick', UploadedFile::fake()->create('staff_list.xlsx', 30))
            ->assertSee('Save 2 Documents')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false);

        $docs = AdminDocument::orderBy('id')->get();
        $this->assertSame(['Timetable', 'Staff list'], $docs->pluck('title')->all());
        $this->assertSame([1, 1], $docs->pluck('organization_id')->map(fn ($id) => (int) $id)->all());
    }
}
