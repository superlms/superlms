<?php

namespace Tests\Feature;

use App\Livewire\Components\NavBar;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Livewire\SuperAdmin\Session as SessionPage;
use App\Models\AcademicSession;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Session: academic sessions (2026–27 …) added, renamed by
 * their dates, never overlapping, one of them current — the one every top
 * bar shows. It sits right after Documents in the menu and on Quick Links.
 */
class SuperAdminSessionTest extends TestCase
{
    private int $year;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);
        $this->year = (int) now()->year;
    }

    private function migrate(): void
    {
        (require base_path('database/migrations/2026_11_03_100000_create_academic_sessions_table.php'))->up();
    }

    private function label(int $from): string
    {
        return $from . '–' . substr((string) ($from + 1), -2);
    }

    public function test_without_sessions_the_top_bar_keeps_this_years_label(): void
    {
        // Before the migration has run, nothing breaks.
        $this->assertSame($this->label($this->year), AcademicSession::currentLabel());
    }

    public function test_the_migration_starts_with_the_session_the_top_bar_showed(): void
    {
        $this->migrate();
        $this->migrate(); // run twice: still one

        $session = AcademicSession::sole();
        $this->assertSame($this->label($this->year), $session->name);
        $this->assertTrue($session->is_current);
        $this->assertSame("{$this->year}-04-01", $session->starts_on->toDateString());
        $this->assertSame(($this->year + 1) . '-03-31', $session->ends_on->toDateString());
        $this->assertSame(12, $session->months());
        $this->assertSame($this->label($this->year), AcademicSession::currentLabel());
    }

    public function test_a_new_session_follows_the_latest_and_is_named_by_its_dates(): void
    {
        $this->migrate();
        $next = $this->year + 1;

        Livewire::test(SessionPage::class)
            ->assertSee('Current: ' . $this->label($this->year))
            ->call('openCreate')
            ->assertSet('startsOn', "{$next}-04-01")
            ->assertSet('endsOn', ($next + 1) . '-03-31')
            ->assertSet('name', $this->label($next))
            ->assertSet('makeCurrent', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false)
            ->assertSee($this->label($next));

        $this->assertSame(2, AcademicSession::count());
        // Adding a session doesn't move the current one.
        $this->assertSame($this->label($this->year), AcademicSession::currentLabel());
        $this->assertSame('upcoming', AcademicSession::where('name', $this->label($next))->sole()->status());
    }

    public function test_the_dates_rename_it_until_a_name_is_typed(): void
    {
        $this->migrate();

        $page = Livewire::test(SessionPage::class)->call('openCreate')
            ->set('startsOn', '2030-06-01')
            ->assertSet('endsOn', '2031-05-31')
            ->assertSet('name', '2030–31')
            ->set('endsOn', '2030-12-31')
            ->assertSet('name', '2030');

        $page->set('name', 'Batch A')
            ->set('endsOn', '2031-04-30')
            ->assertSet('name', 'Batch A')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(AcademicSession::where('name', 'Batch A')->exists());
    }

    public function test_overlapping_backwards_too_long_or_duplicate_sessions_are_refused(): void
    {
        $this->migrate();

        $page = Livewire::test(SessionPage::class)->call('openCreate');

        $page->set('startsOn', "{$this->year}-10-01")->set('endsOn', ($this->year + 1) . '-09-30')->call('save')
            ->assertHasErrors('startsOn')
            ->assertSee('These dates overlap ' . $this->label($this->year));

        $page->set('startsOn', '2035-04-01')->set('endsOn', '2035-03-01')->call('save')
            ->assertHasErrors(['endsOn' => 'after']);

        $page->set('startsOn', '2035-04-01')->set('endsOn', '2037-04-01')->call('save')
            ->assertSee('A session can be at most 2 years long.');

        $page->set('startsOn', '2035-04-01')->set('endsOn', '2036-03-31')->set('name', $this->label($this->year))->call('save')
            ->assertHasErrors(['name' => 'unique']);

        $this->assertSame(1, AcademicSession::count());
    }

    public function test_setting_another_session_current_moves_the_top_bar(): void
    {
        $this->migrate();
        $next = AcademicSession::create(['name' => $this->label($this->year + 1), 'starts_on' => ($this->year + 1) . '-04-01', 'ends_on' => ($this->year + 2) . '-03-31']);

        Livewire::test(SessionPage::class)->call('setCurrent', $next->id)
            ->assertSee('Current: ' . $next->name);

        $this->assertSame([$next->id], AcademicSession::where('is_current', true)->pluck('id')->all());
        $this->assertSame($next->name, AcademicSession::currentLabel());
    }

    public function test_the_current_session_cannot_be_deleted_others_can(): void
    {
        $this->migrate();
        $current = AcademicSession::sole();
        $old = AcademicSession::create(['name' => $this->label($this->year - 1), 'starts_on' => ($this->year - 1) . '-04-01', 'ends_on' => "{$this->year}-03-31"]);

        Livewire::test(SessionPage::class)
            ->call('confirmDelete', $current->id)->assertSet('deleteId', null)
            ->call('confirmDelete', $old->id)->assertSet('deleteId', $old->id)
            ->call('delete')->assertSet('deleteId', null);

        $this->assertSame([$current->id], AcademicSession::pluck('id')->all());
        $this->assertSame('completed', $old->status());
    }

    public function test_the_first_session_added_becomes_current(): void
    {
        $this->migrate();
        DB::table('academic_sessions')->delete();

        Livewire::test(SessionPage::class)->assertSee('No sessions yet')
            ->call('openCreate')->assertSet('makeCurrent', true)
            ->set('makeCurrent', false)
            ->call('save');

        $this->assertTrue(AcademicSession::sole()->is_current);
    }

    public function test_the_top_bar_switches_the_current_session_for_those_allowed(): void
    {
        $this->migrate();
        $first = AcademicSession::sole();
        $this->usersTable();
        $this->actingAs(User::forceCreate(['name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin']));

        // One session: the badge only, no switcher.
        Livewire::test(NavBar::class)->assertSee($first->name)->assertDontSee('Manage sessions');

        $next = AcademicSession::create(['name' => $this->label($this->year + 1), 'starts_on' => ($this->year + 1) . '-04-01', 'ends_on' => ($this->year + 2) . '-03-31']);

        Livewire::test(NavBar::class)
            ->assertSee('Manage sessions')
            ->call('switchSession', $next->id)
            ->assertDispatched('academic-session-changed');
        $this->assertSame($next->name, AcademicSession::currentLabel());

        // A sub super-admin without Session granted sees no switcher and can't switch.
        $this->actingAs(User::forceCreate(['name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin', 'permissions' => ['super-admin.documents']]));
        Livewire::test(NavBar::class)
            ->assertDontSee('Manage sessions')
            ->call('switchSession', $first->id)
            ->assertNotDispatched('academic-session-changed');
        $this->assertSame($next->name, AcademicSession::currentLabel());
    }

    private function usersTable(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('password')->nullable(); $t->text('permissions')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
    }

    public function test_session_comes_right_after_documents_in_the_menu_and_on_quick_links(): void
    {
        $this->migrate();
        $links = array_column(config('menu.super-admin'), 'link');
        $this->assertSame('super-admin.sessions', $links[array_search('super-admin.documents', $links, true) + 1]);

        $this->usersTable();
        $this->actingAs(User::forceCreate(['name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin']));

        $tiles = Livewire::test(QuickLinks::class)->get('links');
        $routes = array_column($tiles, 'route');

        $this->assertSame('super-admin.sessions', end($routes));
        $this->assertLessThanOrEqual(30, count($tiles), 'Quick Links is a 6 × 5 grid.');
    }
}
