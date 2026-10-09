<?php

namespace Tests\Feature;

use App\Livewire\Admin\Documents;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Documents: two tabs — School Documents (the school's own: Add Doc, View,
 * Download, Edit, Delete) first, and Admin Documents (sent by the Super Admin:
 * View and Download only) — each a list with a tile for the file's kind.
 */
class DocumentsTabsTest extends TestCase
{
    private int $org = 6;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('d', 32))]);
        Storage::fake('s3');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('admin_documents', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('title'); $t->text('description')->nullable();
            $t->string('file_path')->nullable(); $t->string('file_name')->nullable(); $t->unsignedBigInteger('file_size')->nullable();
            $t->string('mime_type')->nullable(); $t->unsignedBigInteger('uploaded_by')->nullable(); $t->timestamps();
        });
        Schema::create('super_admin_documents', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->text('description')->nullable(); $t->string('file_path')->nullable();
            $t->string('file_name')->nullable(); $t->unsignedBigInteger('file_size')->nullable(); $t->string('mime_type')->nullable();
            $t->string('audience_scope')->default('all'); $t->unsignedBigInteger('uploaded_by')->nullable(); $t->timestamps();
        });
        Schema::create('super_admin_document_organization', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('document_id'); $t->unsignedBigInteger('organization_id');
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));

        DB::table('admin_documents')->insert([
            ['organization_id' => $this->org, 'title' => 'Fee Structure', 'file_path' => 'admin/documents/fee.pdf', 'file_name' => 'fee.pdf', 'file_size' => 2048, 'created_at' => now(), 'updated_at' => now()],
            ['organization_id' => 99, 'title' => 'Other School Doc', 'file_path' => 'x.pdf', 'file_name' => 'x.pdf', 'file_size' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('super_admin_documents')->insert([
            ['title' => 'Circular for all', 'file_path' => 'super/c.docx', 'file_name' => 'circular.docx', 'file_size' => 5000, 'audience_scope' => 'all', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Kept private', 'file_path' => 'super/p.pdf', 'file_name' => 'p.pdf', 'file_size' => 5, 'audience_scope' => 'private', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_school_tab_first_with_full_actions(): void
    {
        $page = Livewire::test(Documents::class);
        $html = $page->html();

        $this->assertSame('school', $page->get('tab'));
        $this->assertStringContainsString('School Documents', $html);
        $this->assertStringContainsString('Admin Documents', $html);
        $this->assertLessThan(strpos($html, 'Admin Documents'), strpos($html, 'School Documents'));
        $this->assertStringContainsString('wire:click="openCreate"', $html);             // Add Doc
        $this->assertStringContainsString('Fee Structure', $html);
        $this->assertStringNotContainsString('Other School Doc', $html);
        $this->assertStringNotContainsString('Circular for all', $html);                // the Super Admin's are in their own tab
        $this->assertStringContainsString('>PDF</span>', $html);                         // the file's kind
        foreach (['downloadDocument(', 'edit(', 'confirmDelete('] as $action) {
            $this->assertStringContainsString('wire:click="' . $action, $html);
        }
        $this->assertStringContainsString('title="View"', $html);

        // The header as the other pages have it (no back arrow), and no file names in the list.
        $this->assertStringNotContainsString('Back to More', $html);
        $this->assertStringContainsString('School: <strong class="text-gray-800">1</strong>', $html);
        $this->assertStringNotContainsString('>fee.pdf', $html);                      // only in the View link, not shown
        $this->assertStringContainsString('2 KB', $html);
    }

    public function test_admin_tab_is_view_and_download_only(): void
    {
        $page = Livewire::test(Documents::class)->call('setTab', 'admin');
        $html = $page->html();

        $this->assertSame('admin', $page->get('tab'));
        $this->assertStringContainsString('Circular for all', $html);
        $this->assertStringNotContainsString('Kept private', $html);
        $this->assertStringContainsString('>DOC</span>', $html);
        $this->assertStringNotContainsString('circular.docx', $html);
        $this->assertStringContainsString('wire:click="downloadShared(', $html);
        $this->assertStringContainsString('title="View"', $html);
        $this->assertStringNotContainsString('wire:click="openCreate"', $html);          // no Add Doc here
        $this->assertStringNotContainsString('wire:click="edit(', $html);
        $this->assertStringNotContainsString('wire:click="confirmDelete(', $html);
        $this->assertStringNotContainsString('Fee Structure', $html);

        $this->assertSame('school', $page->call('setTab', 'anything')->get('tab'));
    }
}
