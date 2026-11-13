<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Calendar;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Livewire\SuperAdmin\Users;
use App\Models\SuperAdmin\SuperAdminCalendarEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Calendar: each person's own meetings, tasks and events. A
 * sub-super-admin given Calendar has a calendar of their own and never sees,
 * changes or deletes the super admin's.
 */
class SuperAdminCalendarTest extends TestCase
{
    private User $main;
    private User $sub;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('c', 32))]);
        Carbon::setTestNow('2026-11-12 10:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->text('permissions')->nullable();
            $t->unsignedBigInteger('allowed_organization_id')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_11_12_120000_create_super_admin_calendar_events_table.php'))->up();
        // The Queries tab (website demo requests) and its booking settings.
        foreach ([
            '2026_03_07_200536_create_website_demos_table.php',
            '2026_06_29_000002_add_schedule_call_fields_to_website_demos_table.php',
            '2026_11_13_100000_add_booking_fields_to_website_demos_table.php',
            '2026_11_01_160100_create_platform_settings_table.php',
        ] as $migration) {
            (require base_path('database/migrations/' . $migration))->up();
        }
        (new \ReflectionProperty(\App\Support\Platform::class, 'settings'))->setValue(null, null);

        $this->main = User::forceCreate(['name' => 'Main', 'email' => 'main@example.com', 'role' => 'super-admin']);
        $this->sub  = User::forceCreate([
            'name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin',
            'permissions' => ['super-admin.calendar'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calendar_comes_right_after_fees_in_the_menu_and_can_be_given_to_a_sub_super_admin(): void
    {
        $links = array_column(config('menu.super-admin'), 'link');
        $this->assertSame('super-admin.calendar', $links[array_search('super-admin.fees', $links, true) + 1]);
        $this->assertTrue(Route::has('super-admin.calendar'));
        $this->assertArrayHasKey('super-admin.calendar', (new Users())->permissionCatalog());

        $this->actingAs($this->main);
        $routes = array_column(Livewire::test(QuickLinks::class)->get('links'), 'route');
        $this->assertSame('super-admin.calendar', $routes[array_search('super-admin.fees', $routes, true) + 1]);

        // Only a sub-super-admin who was given it may open it.
        $this->assertTrue($this->sub->fresh()->canAccessSuperAdminRoute('super-admin.calendar'));
        $other = User::forceCreate(['name' => 'Other', 'role' => 'sub-super-admin', 'permissions' => ['super-admin.fees']]);
        $this->assertFalse($other->fresh()->canAccessSuperAdminRoute('super-admin.calendar'));
    }

    public function test_a_meeting_is_added_with_whom_and_when_and_shows_under_today(): void
    {
        $this->actingAs($this->main);

        Livewire::test(Calendar::class)
            ->assertSee('Nothing planned for today.')
            ->call('openCreate', '2026-11-12', 'meeting')
            ->set('fTitle', 'Fee review')
            ->set('fStart', '11:30')
            ->set('fEnd', '12:15')
            ->set('fWith', 'Principal, Doon School')
            ->set('fLocation', 'https://meet.example.com/abc')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false)
            ->assertSee('Fee review')
            ->assertSee('With Principal, Doon School')
            ->assertSee('11:30 AM – 12:15 PM')
            ->assertSee('https://meet.example.com/abc');

        $event = SuperAdminCalendarEvent::sole();
        $this->assertSame($this->main->id, (int) $event->user_id);
        $this->assertSame('meeting', $event->type);
        $this->assertSame('2026-11-12', $event->date->toDateString());
        $this->assertSame('11:30', SuperAdminCalendarEvent::hm($event->start_time));
    }

    public function test_a_title_is_needed_and_the_end_must_come_after_the_start(): void
    {
        $this->actingAs($this->main);

        Livewire::test(Calendar::class)
            ->call('openCreate', '2026-11-12', 'meeting')
            ->set('fStart', '15:00')
            ->set('fEnd', '14:00')
            ->call('save')
            ->assertHasErrors(['fTitle', 'fEnd'])
            ->assertSet('showPanel', true);

        $this->assertSame(0, SuperAdminCalendarEvent::count());
    }

    public function test_a_task_is_ticked_off_and_an_open_one_from_an_earlier_day_is_listed(): void
    {
        $this->actingAs($this->main);
        $today = SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'task', 'title' => 'Call the bank', 'date' => '2026-11-12']);
        SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'task', 'title' => 'Send the invoices', 'date' => '2026-11-09']);
        SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'task', 'title' => 'Old and done', 'date' => '2026-10-20', 'is_done' => true]);

        Livewire::test(Calendar::class)
            ->assertSee('0/1')
            ->assertSee('Not done yet')
            ->assertSee('Send the invoices')
            ->assertDontSee('Old and done')
            ->call('toggleDone', $today->id)
            ->assertSee('1/1');

        $this->assertTrue($today->fresh()->is_done);
        $this->assertNotNull($today->fresh()->done_at);
    }

    public function test_a_sub_super_admin_has_their_own_calendar_and_never_sees_or_touches_the_super_admins(): void
    {
        $mine = SuperAdminCalendarEvent::create([
            'user_id' => $this->main->id, 'type' => 'meeting', 'title' => 'Board meeting', 'date' => '2026-11-12',
            'with_whom' => 'Investors',
        ]);
        $task = SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'task', 'title' => 'Sign the payroll', 'date' => '2026-11-10']);

        $this->actingAs($this->sub->fresh());

        $page = Livewire::test(Calendar::class)
            ->assertDontSee('Board meeting')
            ->assertDontSee('Investors')
            ->assertDontSee('Sign the payroll')
            ->assertSee('Nothing planned for today.');

        // Asking for the super admin's rows by id does nothing.
        $page->call('openEdit', $mine->id)->assertSet('showPanel', false)->assertSet('editingId', null)
            ->call('toggleDone', $task->id)
            ->call('confirmDelete', $mine->id)->assertSet('deleteId', null)
            ->set('deleteId', $mine->id)->call('delete');

        $this->assertNotNull($mine->fresh());
        $this->assertFalse($task->fresh()->is_done);

        // Editing someone else's row by id is not possible either.
        $page->set('editingId', $mine->id)->set('showPanel', true)->set('fType', 'meeting')
            ->set('fTitle', 'Hijacked')->set('fDate', '2026-11-12')->call('save');
        $this->assertSame('Board meeting', $mine->fresh()->title);

        // What the sub-super-admin adds is theirs, and the super admin does not see it.
        $page->call('openCreate', '2026-11-12', 'task')->set('fTitle', 'Visit the school')->call('save')
            ->assertSee('Visit the school');
        $this->assertSame($this->sub->id, (int) SuperAdminCalendarEvent::where('title', 'Visit the school')->value('user_id'));

        $this->actingAs($this->main);
        Livewire::test(Calendar::class)
            ->assertSee('Board meeting')
            ->assertDontSee('Visit the school');
    }

    public function test_the_month_moves_and_a_day_picked_shows_its_own_list(): void
    {
        $this->actingAs($this->main);
        SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'event', 'title' => 'Annual function', 'date' => '2026-12-16']);

        Livewire::test(Calendar::class)
            ->assertSee('November 2026')
            ->assertDontSee('Annual function')
            ->call('nextMonth')
            ->assertSee('December 2026')
            ->assertSee('Annual function')
            ->call('selectDay', '2026-12-16')
            ->assertSet('day', '2026-12-16')
            ->assertSee('Wednesday')
            ->call('setType', 'meeting')
            ->assertDontSee('Annual function')
            ->call('goToday')
            ->assertSet('month', '2026-11')
            ->assertSet('day', '2026-11-12');
    }
}
