<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\AboutApp;
use App\Livewire\SuperAdmin\Documents;
use App\Livewire\SuperAdmin\Rating;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * User's asks of 2026-10-13: Documents keeps its columns whatever the content
 * (a long title or description ends in "…"), About App's add / edit forms in
 * the Students form's look, and School Reviews lists each school's logo.
 */
class SuperAdminDocsAboutRatingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('d', 32))]);

        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 903;
        $user->role = 'super-admin';
        $user->permissions = [];
        $this->actingAs($user);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('logo')->nullable();
            $t->timestamps();
        });
    }

    public function test_documents_keep_their_columns_and_cut_long_text_with_an_ellipsis(): void
    {
        Storage::fake('s3');
        Schema::create('super_admin_documents', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            foreach (['file_path', 'file_name', 'mime_type'] as $c) {
                $t->string($c)->nullable();
            }
            $t->unsignedBigInteger('file_size')->nullable();
            $t->string('audience_scope')->default('all');
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamps();
        });
        Schema::create('super_admin_document_organization', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('document_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        $long = str_repeat('SuperLMS is a cloud-based school management system. ', 4);
        DB::table('super_admin_documents')->insert([
            'title' => $long, 'description' => $long, 'file_path' => 'super/a.pdf', 'file_name' => 'a.pdf',
            'file_size' => 1024, 'audience_scope' => 'private', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = Livewire::test(Documents::class)->html();

        $this->assertStringContainsString('table-fixed', $html);
        $this->assertStringContainsString('w-[22%]">Document</th>', $html);
        $this->assertStringContainsString('<p class="text-sm font-semibold text-gray-900 truncate" title="' . e($long) . '">', $html);
        $this->assertStringContainsString('<span class="block truncate" title="' . e($long) . '">', $html);
        $this->assertStringNotContainsString('line-clamp-2', $html);
    }

    public function test_about_app_forms_are_in_the_students_form_look(): void
    {
        Schema::create('about_apps', function (Blueprint $t) {
            $t->id();
            foreach (['heading', 'sub_heading', 'logo', 'company_name', 'company_cin', 'address'] as $c) {
                $t->string($c)->nullable();
            }
            foreach (['content', 'contact_details', 'core_team', 'social_media', 'documents'] as $c) {
                $t->json($c)->nullable();
            }
            $t->timestamps();
        });
        DB::table('about_apps')->insert([
            'heading' => 'About SuperLMS',
            'core_team' => json_encode([['name' => 'Asha', 'position' => 'Founder', 'image' => 'https://example.com/asha.jpg']]),
            'social_media' => json_encode([['platform' => 'instagram', 'url' => 'https://instagram.com/superlms']]),
            'documents' => json_encode([['title' => 'Brochure', 'file_type' => 'pdf', 'file_path' => 'https://example.com/b.pdf', 'file_size' => 204800]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = Livewire::test(AboutApp::class)->call('setTab', 'edit');

        foreach ([['openTeamModal', 0, 'Edit Team Member', 'saveTeamMember'], ['openSocialModal', 0, 'Edit Social Media', 'saveSocialMedia'],
                  ['openDocumentModal', 0, 'Edit Document', 'saveDocument'], ['openContactModal', null, 'Add Contact', 'saveContact']] as [$open, $index, $title, $save]) {
            $html = $page->call($open, $index)->html();

            $this->assertStringContainsString('w-full max-w-3xl bg-white shadow-2xl', $html, $title);
            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString('wire:click="' . $save . '"', $html);
            $this->assertStringContainsString('px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white', $html);
            $this->assertStringNotContainsString('bg-amber-500', $html);
        }

        // The team member's saved photo sits in the Students photo row.
        $team = $page->call('openTeamModal', 0)->html();
        $this->assertStringContainsString('src="https://example.com/asha.jpg" class="w-12 h-12 rounded-full', $team);
        $this->assertStringContainsString('wire:model="newTeamMember.name"', $team);
    }

    public function test_school_reviews_list_the_schools_logo(): void
    {
        Schema::create('rate_lms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->text('feedback')->nullable();
            $t->unsignedTinyInteger('rating');
            $t->unsignedTinyInteger('status')->default(1);
            $t->timestamps();
        });
        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Sunrise Public School', 'logo' => 'https://example.com/sunrise.png'],
            ['id' => 2, 'name' => 'Green Valley School', 'logo' => null],
        ]);
        DB::table('rate_lms')->insert([
            ['organization_id' => 1, 'feedback' => 'Easy to use.', 'rating' => 4, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['organization_id' => 2, 'feedback' => 'Good app.', 'rating' => 5, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $html = Livewire::test(Rating::class)->html();

        $this->assertStringContainsString('<img src="https://example.com/sunrise.png" alt="" class="w-9 h-9 rounded-full object-cover', $html);
        $this->assertStringContainsString('<span class="text-xs font-semibold text-indigo-600">G</span>', $html);
        $this->assertStringNotContainsString('M11.48 3.499', $html); // the star icon is gone from the list
    }
}
