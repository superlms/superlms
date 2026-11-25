<?php

namespace Tests\Feature;

use App\Livewire\Admin\More;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The school panel's sidebar and its More screen: Documents is in the sidebar,
 * under Lists; Exam Copy is a tile on More. Exam Copy stays in the menu list,
 * so the page search, Quick Links and the screens a sub-admin can be granted
 * still find it.
 */
class AdminSidebarMoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('module_organization', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('module_key');
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
        $this->forgetModuleTable();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('g', 32))]);
    }

    protected function tearDown(): void
    {
        $this->forgetModuleTable();
        parent::tearDown();
    }

    /** The model remembers whether the modules table exists for the whole process. */
    private function forgetModuleTable(): void
    {
        $known = new \ReflectionProperty(Organization::class, 'moduleTableExists');
        $known->setAccessible(true);
        $known->setValue(null, null);
    }

    private function signIn(string $role = 'admin', array $permissions = []): User
    {
        $org = new Organization(['name' => 'Test School']);
        $org->id = 8;

        $user = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $user->id = 500;
        $user->role = $role;
        $user->organization_id = 8;
        $user->permissions = $permissions;
        $user->setRelation('organization', $org);
        $this->actingAs($user);

        return $user;
    }

    /** The titles the desktop sidebar lists, in order. */
    private function sidebarTitles(): array
    {
        $html = view('admin-components.admin-sidebar')->render();
        preg_match_all('/<span class="lms-label">(.*?)<\/span>/s', $html, $m);

        return array_map(fn ($t) => html_entity_decode(trim($t)), $m[1]);
    }

    public function test_documents_is_in_the_sidebar_under_lists_and_exam_copy_is_not(): void
    {
        $this->signIn();
        $titles = $this->sidebarTitles();

        $lists = array_search('Lists', $titles, true);
        $this->assertNotFalse($lists);
        $this->assertSame('Documents', $titles[$lists + 1]);
        $this->assertSame('Exam', $titles[$lists + 2]);
        $this->assertNotContains('Exam Copy', $titles);
        $this->assertSame('More', end($titles));

        // The phone's drawer lists the same.
        $html = view('admin-components.admin-sidebar')->render();
        $this->assertSame(2, substr_count($html, '/documents"'));
        $this->assertSame(0, substr_count($html, '/exam-copy"'));
    }

    public function test_exam_copy_is_still_in_the_menu_list(): void
    {
        $menu = collect(config('menu.admin'))->keyBy('link');

        $this->assertSame('Exam Copy', $menu['admin.exam-copy']['title']);
        $this->assertFalse($menu['admin.exam-copy']['sidebar']);
        $this->assertSame('Documents', $menu['admin.documents']['title']);
        $this->assertArrayNotHasKey('sidebar', $menu['admin.documents']);

        // The screens a sub-admin can be granted come from that list.
        $this->signIn();
        $catalog = (new \App\Livewire\Admin\Users())->permissionCatalog();
        $this->assertSame('Exam Copy', $catalog['admin.exam-copy']);
        $this->assertSame('Documents', $catalog['admin.documents']);
    }

    public function test_quick_links_are_the_sidebars_own_list(): void
    {
        // The full admin: every sidebar screen but Quick Links itself — so
        // Documents is a tile and Exam Copy, which is not in the sidebar, is not.
        $this->signIn();
        $tiles = array_column(Livewire::test(\App\Livewire\Admin\QuickLinks::class)->get('links'), 'title');
        $sidebar = array_values(array_diff($this->sidebarTitles(), ['Quick Links']));

        $this->assertSame($sidebar, $tiles);
        $this->assertContains('Documents', $tiles);
        $this->assertNotContains('Exam Copy', $tiles);
        $this->assertNotContains('Quick Links', $tiles);

        // A sub-admin: only the screens granted — as the sidebar shows them.
        $this->signIn('sub-admin', ['admin.student', 'admin.exam-copy', 'admin.documents']);
        $tiles = array_column(Livewire::test(\App\Livewire\Admin\QuickLinks::class)->get('links'), 'title');
        $this->assertSame(['Students', 'Documents'], $tiles);
        $this->assertSame($this->sidebarTitles(), $tiles);

        // A school without the exam module: its screens are tiles for nobody.
        DB::table('module_organization')->insert(['organization_id' => 8, 'module_key' => 'exam', 'enabled' => false]);
        $this->signIn();
        $tiles = array_column(Livewire::test(\App\Livewire\Admin\QuickLinks::class)->get('links'), 'title');
        $this->assertNotContains('Exam', $tiles);
        $this->assertNotContains('Seating Plan', $tiles);
    }

    public function test_more_has_exam_copy_and_no_documents(): void
    {
        $this->signIn();

        $routes = array_column(Livewire::test(More::class)->get('items'), 'route');

        $this->assertContains('admin.exam-copy', $routes);
        $this->assertNotContains('admin.documents', $routes);
        $this->assertSame('admin.boards', $routes[3]);      // the Class 9–12 students, after Admissions (2026-11-25)
        $this->assertSame('admin.exam-copy', $routes[5]);   // where Documents was
        $this->assertSame('admin.messaging', $routes[6]);   // the messaging wallet (2026-11-01)
        // Reception, Gatepass and Complaints right after it (2026-11-04).
        $this->assertSame('admin.push-notification', $routes[7]);   // the school's own push (2026-11-13)
        $this->assertSame(['admin.reception', 'admin.gatepass', 'admin.complaints'], array_slice($routes, 8, 3));
        $this->assertCount(19, $routes);                     // Credit is no longer a tile (2026-10-09)
        $this->assertNotContains('admin.credit', $routes);

        Livewire::test(More::class)->assertSee('Exam Copy')->assertSeeHtml('/exam-copy"');
    }

    /** Their screens themselves: AdminFrontDeskTest. */
    public function test_reception_gatepass_and_complaints_are_tiles_on_more(): void
    {
        $this->signIn();

        foreach (['Reception' => '/reception"', 'Gatepass' => '/gatepass"', 'Complaints' => '/complaints"'] as $title => $href) {
            Livewire::test(More::class)->assertSee($title)->assertSeeHtml($href);
        }
    }

    public function test_a_school_without_the_exam_module_has_no_exam_copy_tile(): void
    {
        DB::table('module_organization')->insert(['organization_id' => 8, 'module_key' => 'exam', 'enabled' => false]);
        $this->signIn();

        $routes = array_column(Livewire::test(More::class)->get('items'), 'route');

        $this->assertNotContains('admin.exam-copy', $routes);
        $this->assertContains('admin.profile', $routes);
        $this->assertCount(18, $routes);
    }

    public function test_a_sub_admin_sees_each_only_when_granted(): void
    {
        $this->signIn('sub-admin', ['admin.students']);
        $this->assertNotContains('admin.exam-copy', array_column(Livewire::test(More::class)->get('items'), 'route'));
        $this->assertNotContains('Documents', $this->sidebarTitles());

        $this->signIn('sub-admin', ['admin.students', 'admin.exam-copy', 'admin.documents']);
        $this->assertContains('admin.exam-copy', array_column(Livewire::test(More::class)->get('items'), 'route'));
        $titles = $this->sidebarTitles();
        $this->assertContains('Documents', $titles);
        $this->assertNotContains('Exam Copy', $titles);
    }

    /**
     * The school panel in the Super Admin's exact style (2026-11-19): the
     * sidebar's look (sa-*), always open, the school's logo and name on top
     * and who is signed in at the foot with Profile and Logout; Quick Links
     * with the Super Admin's own header (no top bar on a computer); the
     * Super Admin's logout card.
     */
    public function test_the_school_panel_is_in_the_super_admins_style(): void
    {
        $this->signIn();

        $html = view('admin-components.admin-sidebar')->render();
        foreach (['class="sa-side', 'class="sa-brand is-school"', 'Test School', 'class="sa-user"', 'class="sa-link',
                  "Livewire.dispatch('open-logout-confirm')", '/8/profile"', 'School Admin · ',
                  '#sidebar-nav::-webkit-scrollbar { width: 3px; }', 'html.sidebar-expanded { --lms-sb: 226px; }'] as $seen) {
            $this->assertStringContainsString($seen, $html);
        }
        // No menu button and none of the old purple rows.
        $this->assertStringNotContainsString('Collapse menu', $html);
        $this->assertStringNotContainsString('bg-purple-50', $html);

        Livewire::test(\App\Livewire\Admin\QuickLinks::class)
            ->assertSeeHtml('class="sa-skin sk-full sk-ql flex flex-col"')
            ->assertSeeHtml('class="sk-head"')
            ->assertSeeHtml("Livewire.dispatch('open-notifications')")
            ->assertSeeHtml('class="sk-tile"')
            // 2026-11-19: "Welcome! School Admin", no Filter by; the time over
            // the date, then the session, then Messages before the bell.
            ->assertSeeHtml('<h1><span class="sk-hi">Welcome!</span> School Admin</h1>')
            ->assertDontSee('Filter by:')
            ->assertSeeHtmlInOrder(['class="sk-clock"', 'class="sk-pill sk-session"', 'class="sk-actions"', 'class="sk-btn sk-bell"'])
            ->assertDontSeeHtml('ql-grid');

        Livewire::test(\App\Livewire\Components\NavBar::class)
            ->call('openLogoutConfirm')
            ->assertSet('showAdminLogoutModal', true)
            ->assertSeeHtml('class="lo-card"')
            ->assertSee('Logout from the School Admin panel?')
            ->assertSeeHtml('wire:click="adminLogout"')
            ->assertDontSeeHtml('wire:click="superAdminLogout"');
    }

    public function test_the_school_admins_sign_in_screens_are_the_super_admins(): void
    {
        foreach (['/login' => 'School Admin <span class="grad">Login</span>', '/reset-password' => 'Forgot <span class="grad">Password?</span>'] as $url => $heading) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('class="sa-auth"', $html);
            $this->assertStringContainsString('class="sa-auth-card"', $html);
            $this->assertStringContainsString($heading, $html);
            $this->assertStringContainsString('class="am am-', $html);   // the step acted out beside the form
            $this->assertStringNotContainsString('bg-violet-600', $html);
            $this->assertStringNotContainsString('from-violet-50', $html);
        }

        // The window in the picture names the school panel's own screen.
        $this->assertStringContainsString('<div class="am-h ">School Admin Login</div>', $this->get('/login')->getContent());
    }
}
