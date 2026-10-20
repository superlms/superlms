<?php

namespace Tests\Feature;

use App\Http\Controllers\LiveNotificationController;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\SuperAdmin\Schools;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A school already open on the browser (one the super-admin opened from
 * Schools → Login) no longer keeps another school's admin out: the login page
 * shows its form, and signing in moves the browser — every admin tab — to the
 * new school, the super-admin staying signed in. Notifications that come in
 * while the admin or super-admin works are handed to the page for a toast
 * and a voice, apart from their own doings. Super Admin's Schools lists A to Z.
 */
class SchoolSwitchLiveNotifyTest extends TestCase
{
    private const PASSWORD = 'Secret#123';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('password')->nullable();
            $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true); $t->text('permissions')->nullable();
            $t->string('remember_token')->nullable(); $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->string('type'); $t->morphs('notifiable'); $t->text('data');
            $t->timestamp('read_at')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert([['id' => 9, 'name' => 'Doon School'], ['id' => 10, 'name' => 'Aakash School']]);
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('k', 32)),
            'services.otp.login_enabled' => false,
        ]);
    }

    private function user(string $role, ?int $org, string $email): User
    {
        return User::forceCreate([
            'name' => ucfirst($role) . " {$org}", 'email' => $email, 'password' => Hash::make(self::PASSWORD),
            'role' => $role, 'organization_id' => $org, 'is_active' => true,
        ]);
    }

    public function test_another_schools_admin_signs_in_over_a_school_already_open(): void
    {
        $super = $this->user('super-admin', null, 'super@example.com');
        $doon  = $this->user('admin', 9, 'doon@example.com');
        $this->user('admin', 10, 'aakash@example.com');

        // The login page is no longer guest-only (it sent everyone back to the open school).
        $this->assertNotContains('guest:admin', Route::getRoutes()->getByName('admin.login')->gatherMiddleware());

        // The super-admin opened Doon School from Schools → Login.
        Auth::guard('superadmin')->login($super);
        Auth::guard('admin')->login($doon);
        $session = session()->getId();

        $page = Livewire::test(AdminLogin::class)
            ->assertSet('signedIn.name', 'Doon School')
            ->assertSee('Signed in to')
            ->assertSee('Continue');

        $page->set('email', 'aakash@example.com')->set('password', self::PASSWORD)->call('login')
            ->assertRedirect(route('admin.quick-links', ['organization' => 10]));

        $this->assertSame(10, (int) Auth::guard('admin')->user()->organization_id);
        $this->assertTrue(Auth::guard('superadmin')->check());
        // The same session: a tab of the old school still in flight cannot put it back.
        $this->assertSame($session, session()->getId());
        // …and the other admin tabs are told to follow.
        $this->assertSame('10', session('school-login')['org']);
    }

    public function test_a_fresh_browser_signs_in_on_a_new_session(): void
    {
        $this->user('admin', 9, 'doon@example.com');
        $session = session()->getId();

        Livewire::test(AdminLogin::class)
            ->assertSet('signedIn', [])
            ->set('email', 'doon@example.com')->set('password', self::PASSWORD)->call('login');

        $this->assertSame(9, (int) Auth::guard('admin')->user()->organization_id);
        $this->assertNotSame($session, session()->getId());
    }

    public function test_the_live_feed_hands_over_what_came_in_since_but_not_ones_own_doings(): void
    {
        $admin = $this->user('admin', 9, 'doon@example.com');

        $this->travelTo(now()->subMinutes(30));
        $admin->notify(new ActivityNotification(['type' => 'created', 'title' => 'Old one', 'body' => '', 'actor_id' => 77]));
        $this->travelBack();
        $admin->notify(new ActivityNotification(['type' => 'created', 'title' => 'Teacher created Homework', 'body' => 'Anita created a Homework', 'actor_id' => 77]));
        $admin->notify(new ActivityNotification(['type' => 'created', 'title' => 'Admin created Student', 'body' => 'You did this', 'actor_id' => $admin->id]));

        $ask = function (?string $since) use ($admin) {
            $request = Request::create('/9/notification/live', 'GET', $since ? ['since' => $since] : []);
            $request->setUserResolver(fn () => $admin);

            return app(LiveNotificationController::class)($request)->getData(true);
        };

        // The first ask only starts the count.
        $first = $ask(null);
        $this->assertNotEmpty($first['now']);
        $this->assertSame([], $first['items']);

        // Since a minute ago: the teacher's, not the admin's own, not the old one.
        $items = $ask(now()->subMinute()->toDateTimeString())['items'];
        $this->assertSame(['Teacher created Homework'], array_column($items, 'title'));
        $this->assertSame('Anita created a Homework', $items[0]['body']);

        // A page back after a long while starts afresh: no backlog.
        $this->assertSame([], $ask(now()->subMinutes(40)->toDateTimeString())['items']);

        // All of them are still unread in the bell.
        $this->assertSame(3, $admin->unreadNotifications()->count());
    }

    public function test_super_admin_schools_list_a_to_z(): void
    {
        $schools = new Schools();
        $this->assertSame('name', $schools->sortBy);
        $this->assertSame('asc', $schools->sortDir);
    }
}
