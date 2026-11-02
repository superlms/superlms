<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Schools;
use App\Models\Organization;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Tests\TestCase;

/**
 * Super Admin → Schools → Modules → Notification: switched off, not one push
 * reaches the phones of that school's people; other schools and the super
 * admin are untouched, and switching it back on brings the pushes back.
 */
class SchoolNotificationModuleTest extends TestCase
{
    /** Tokens each sendMulticast() call was given. */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The table-exists check is cached per process; start each test fresh.
        $flag = new \ReflectionProperty(Organization::class, 'moduleTableExists');
        $flag->setValue(null, null);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('user_fcm_tokens', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->string('token'); $t->string('platform')->nullable(); $t->timestamps();
        });
        Schema::create('module_organization', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('module_key'); $t->boolean('enabled')->default(true); $t->timestamps();
        });

        DB::table('organizations')->insert([['id' => 1, 'name' => 'Sunrise School'], ['id' => 2, 'name' => 'Moon School']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Super', 'role' => 'super-admin', 'organization_id' => null],
            ['id' => 10, 'name' => 'Ravi', 'role' => 'student', 'organization_id' => 1],
            ['id' => 11, 'name' => 'Meena', 'role' => 'teacher', 'organization_id' => 1],
            ['id' => 20, 'name' => 'Zoya', 'role' => 'student', 'organization_id' => 2],
        ]);
        DB::table('user_fcm_tokens')->insert([
            ['user_id' => 1, 'token' => 'tok-super'],
            ['user_id' => 10, 'token' => 'tok-ravi'],
            ['user_id' => 11, 'token' => 'tok-meena'],
            ['user_id' => 20, 'token' => 'tok-zoya'],
        ]);

        $messaging = $this->createMock(Messaging::class);
        $messaging->method('sendMulticast')->willReturnCallback(function ($message, $tokens) {
            $this->sent[] = array_values((array) $tokens);
            return MulticastSendReport::withItems([]);
        });
        $this->app->instance(FirebaseNotificationService::class, new FirebaseNotificationService($messaging));
    }

    private function turnOff(int $orgId, bool $off = true): void
    {
        DB::table('module_organization')->updateOrInsert(
            ['organization_id' => $orgId, 'module_key' => 'notification'],
            ['enabled' => !$off],
        );
    }

    private function sentTokens(): array
    {
        $all = array_merge([], ...$this->sent);
        sort($all);
        return $all;
    }

    public function test_notification_is_the_last_module_and_on_by_default(): void
    {
        $modules = config('modules');
        $this->assertSame('notification', array_key_last($modules));
        $this->assertSame('Notification', $modules['notification']['label']);
        $this->assertArrayHasKey('notification', (new Schools())->shownModules());

        $this->assertTrue(Organization::find(1)->hasModule('notification'));
        $this->assertSame([], Organization::idsWithModuleOff('notification'));
    }

    public function test_every_school_gets_its_pushes_while_the_module_is_on(): void
    {
        app(FirebaseNotificationService::class)->notifyUserIds([1, 10, 11, 20], 'announcement', ['title' => 'Hi']);

        $this->assertSame(['tok-meena', 'tok-ravi', 'tok-super', 'tok-zoya'], $this->sentTokens());
    }

    public function test_a_school_with_the_module_off_gets_no_push_at_all(): void
    {
        $this->turnOff(1);
        $push = app(FirebaseNotificationService::class);

        // Many at once: only the other school and the super admin are reached.
        $push->notifyUserIds([1, 10, 11, 20], 'announcement', ['title' => 'Hi']);
        $this->assertSame(['tok-super', 'tok-zoya'], $this->sentTokens());

        // One user, by model and through the many-users path: nothing goes out.
        $this->sent = [];
        $this->assertFalse($push->notifyUser(User::find(10), 'fee_paid'));
        $this->assertFalse($push->notifyUsers(User::whereIn('id', [10, 11])->get(), 'homework_assigned'));
        $this->assertFalse($push->sendToUser(User::find(11), 'Title', 'Body'));
        $this->assertSame([], $this->sent);

        // The other school still gets its own.
        $push->notifyUser(User::find(20), 'fee_paid');
        $this->assertSame(['tok-zoya'], $this->sentTokens());
    }

    public function test_switching_it_back_on_brings_the_pushes_back(): void
    {
        $this->turnOff(1);
        $this->turnOff(1, false);

        app(FirebaseNotificationService::class)->notifyUser(User::find(10), 'fee_paid');

        $this->assertSame(['tok-ravi'], $this->sentTokens());
    }

    public function test_pushable_user_ids_drops_only_the_muted_school(): void
    {
        $this->turnOff(2);

        $this->assertSame([2], Organization::idsWithModuleOff('notification'));
        $this->assertFalse(Organization::find(2)->hasModule('notification'));
        $this->assertSame([1, 10, 11], FirebaseNotificationService::pushableUserIds([1, 10, 11, 20, 20]));
    }
}
