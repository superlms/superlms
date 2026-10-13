<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\PortalWebsite;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → School Website listed in the Students list's look (user's ask
 * of 2026-10-13): header figures, a filter bar (search + status), S.No, the
 * school with its logo, domain, status as plain text and icon actions.
 */
class SuperAdminSchoolWebsitesListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('w', 32))]);

        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 904;
        $user->role = 'super-admin';
        $this->actingAs($user);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('logo')->nullable();
            $t->timestamps();
        });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('domain')->nullable();
            $t->boolean('status')->default(false);
            $t->timestamps();
        });
        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Alpha School', 'email' => 'alpha@school.in', 'logo' => 'https://example.com/alpha.png'],
            ['id' => 2, 'name' => 'Beta School', 'email' => 'beta@school.in', 'logo' => null],
            ['id' => 3, 'name' => 'Gamma School', 'email' => 'gamma@school.in', 'logo' => null],
        ]);
        DB::table('school_websites')->insert([
            ['organization_id' => 1, 'domain' => 'alpha.superlms.in', 'status' => true],
            ['organization_id' => 2, 'domain' => null, 'status' => false],
        ]);
    }

    private function text(string $html): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($html));
    }

    public function test_the_list_is_in_the_students_look_with_plain_status(): void
    {
        $html = Livewire::test(PortalWebsite::class)->html();
        $text = $this->text($html);

        $this->assertStringContainsString('Schools: 3 Published: 1 Draft: 1 Not created: 1', $text);
        $this->assertStringContainsString('S.No School Domain Status Actions', $text);
        $this->assertStringContainsString('1 Alpha School alpha@school.in alpha.superlms.in Published', $text);
        $this->assertStringContainsString('2 B Beta School beta@school.in — Draft', $text);
        $this->assertStringContainsString('3 G Gamma School gamma@school.in — Not created', $text);
        $this->assertStringContainsString('<img src="https://example.com/alpha.png"', $html);
        $this->assertStringContainsString('href="https://alpha.superlms.in"', $html);
        $this->assertStringContainsString('title="Create website"', $html);
        $this->assertStringNotContainsString('rounded-full bg-green-50', $html);   // no status chips
    }

    public function test_the_status_filter_and_search(): void
    {
        $page = Livewire::test(PortalWebsite::class);

        $draft = $this->text($page->set('statusFilter', 'draft')->html());
        $this->assertStringContainsString('Beta School', $draft);
        $this->assertStringNotContainsString('Alpha School', $draft);

        $none = $this->text($page->set('statusFilter', 'none')->html());
        $this->assertStringContainsString('Gamma School', $none);
        $this->assertStringNotContainsString('Beta School', $none);

        $byEmail = $this->text($page->call('clearFilters')->set('search', 'alpha@')->html());
        $this->assertStringContainsString('Alpha School', $byEmail);
        $this->assertStringNotContainsString('Gamma School', $byEmail);
    }
}
