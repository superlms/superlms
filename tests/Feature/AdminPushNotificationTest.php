<?php

namespace Tests\Feature;

use App\Livewire\Admin\More;
use App\Livewire\Admin\PushNotification;
use App\Models\Organization;
use App\Models\SchoolPushNotification;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin → More → Push Notification: the school's admin sends a push to its own
 * students (all, a class, a section), teachers, everyone, or people picked by
 * name — never anyone of another school — to their phones and bells.
 */
class AdminPushNotificationTest extends TestCase
{
    /** What the fake Firebase was asked to push: [userIds, type, opts]. */
    public array $pushed = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->text('permissions')->nullable();
            $t->string('mobile_number')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('module_organization', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('module_key'); $t->boolean('enabled')->default(true); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('admission_no')->nullable(); $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('employee_id')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('user_fcm_tokens', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->string('token'); $t->string('platform')->nullable(); $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->string('type'); $t->string('notifiable_type'); $t->unsignedBigInteger('notifiable_id');
            $t->text('data'); $t->timestamp('read_at')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_11_13_150000_create_school_push_notifications_table.php'))->up();
        $this->forgetModuleTable();

        DB::table('organizations')->insert([['id' => 8, 'name' => 'Doon School'], ['id' => 9, 'name' => 'Other School']]);
        DB::table('users')->insert([
            ['id' => 500, 'name' => 'Admin', 'role' => 'admin', 'organization_id' => 8],
            ['id' => 101, 'name' => 'aarav', 'role' => 'student', 'organization_id' => 8],
            ['id' => 102, 'name' => 'priya', 'role' => 'student', 'organization_id' => 8],
            ['id' => 103, 'name' => 'rohan', 'role' => 'student', 'organization_id' => 8],
            ['id' => 104, 'name' => 'other kid', 'role' => 'student', 'organization_id' => 9],
            ['id' => 201, 'name' => 'Meena Teacher', 'role' => 'teacher', 'organization_id' => 8],
            ['id' => 202, 'name' => 'Ravi Teacher', 'role' => 'teacher', 'organization_id' => 8],
            ['id' => 203, 'name' => 'Priya Elsewhere', 'role' => 'teacher', 'organization_id' => 9],
        ]);
        DB::table('standards')->insert([
            ['id' => 1, 'organization_id' => 8, 'name' => 'Class 6', 'order' => 6],
            ['id' => 2, 'organization_id' => 8, 'name' => 'Class 7', 'order' => 7],
            ['id' => 3, 'organization_id' => 9, 'name' => 'Class 6', 'order' => 6],
        ]);
        DB::table('sections')->insert([
            ['id' => 1, 'organization_id' => 8, 'standard_id' => 1, 'name' => 'A'],
            ['id' => 2, 'organization_id' => 8, 'standard_id' => 1, 'name' => 'B'],
        ]);
        DB::table('student_details')->insert([
            ['id' => 1, 'organization_id' => 8, 'user_id' => 101, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Aarav Sharma', 'admission_no' => 'A-101'],
            ['id' => 2, 'organization_id' => 8, 'user_id' => 102, 'standard_id' => 1, 'section_id' => 2, 'full_name' => 'Priya Verma', 'admission_no' => 'A-102'],
            ['id' => 3, 'organization_id' => 8, 'user_id' => 103, 'standard_id' => 2, 'section_id' => null, 'full_name' => 'Rohan Gupta', 'admission_no' => 'A-103'],
            ['id' => 4, 'organization_id' => 9, 'user_id' => 104, 'standard_id' => 3, 'section_id' => null, 'full_name' => 'Priya Other', 'admission_no' => 'O-1'],
            ['id' => 5, 'organization_id' => 8, 'user_id' => null, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'No Login', 'admission_no' => 'A-105'],
        ]);
        DB::table('teacher_details')->insert([
            ['id' => 1, 'organization_id' => 8, 'user_id' => 201, 'employee_id' => 'E-1', 'phone' => '9000000001'],
            ['id' => 2, 'organization_id' => 8, 'user_id' => 202, 'employee_id' => 'E-2', 'phone' => '9000000002'],
            ['id' => 3, 'organization_id' => 9, 'user_id' => 203, 'employee_id' => 'X-1', 'phone' => '9000000003'],
        ]);
        DB::table('user_fcm_tokens')->insert([
            ['user_id' => 101, 'token' => 'tok-aarav-a', 'platform' => 'android'],
            ['user_id' => 101, 'token' => 'tok-aarav-i', 'platform' => 'ios'],
            ['user_id' => 201, 'token' => 'tok-meena', 'platform' => 'android'],
            ['user_id' => 104, 'token' => 'tok-other', 'platform' => 'android'],
        ]);

        $test = $this;
        $this->app->instance(FirebaseNotificationService::class, new class($test) {
            public function __construct(private $test) {}
            public function notifyUserIds(array $ids, string $type, array $opts = []): bool
            {
                $this->test->pushed[] = [$ids, $type, $opts];
                return true;
            }
        });

        $admin = User::find(500);
        $admin->setRelation('organization', Organization::find(8));
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        $this->forgetModuleTable();
        parent::tearDown();
    }

    private function forgetModuleTable(): void
    {
        $known = new \ReflectionProperty(Organization::class, 'moduleTableExists');
        $known->setAccessible(true);
        $known->setValue(null, null);
    }

    private function compose(string $audience = 'students')
    {
        return Livewire::test(PushNotification::class)
            ->call('openCompose')
            ->set('title', 'School closed')
            ->set('body', 'Tomorrow is a holiday.')
            ->set('audience', $audience);
    }

    public function test_it_is_a_tile_on_more_and_a_page(): void
    {
        $this->assertTrue(Route::has('admin.push-notification'));
        $routes = array_column(Livewire::test(More::class)->get('items'), 'route');
        $this->assertContains('admin.push-notification', $routes);

        Livewire::test(PushNotification::class)
            ->assertSee('Push Notification')
            ->assertSee('No notifications sent yet')
            ->call('openCompose')
            ->assertSee('Who should receive this?')
            ->assertSee('Pick by name')
            ->assertSee('Class 6')
            ->assertSee('Class 7');
    }

    public function test_a_section_gets_it_on_the_phone_and_in_the_bell_and_it_is_listed(): void
    {
        $this->compose()
            ->set('standardId', '1')
            ->set('sectionId', '1')
            ->call('review')
            ->assertHasNoErrors()
            ->assertSet('confirming', true)
            ->assertSet('previewRecipients', 1)   // Aarav; "No Login" has no login
            ->assertSet('previewDevices', 2)
            ->call('send')
            ->assertSet('confirming', false)
            ->assertSet('composing', false)
            ->assertSee('Class 6 · Section A');

        $this->assertSame([[[101], 'promo', ['title' => 'School closed', 'body' => 'Tomorrow is a holiday.']]], $this->pushed);
        $this->assertSame([101], DB::table('notifications')->where('type', 'promo')->pluck('notifiable_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(
            ['type' => 'promo', 'title' => 'School closed', 'body' => 'Tomorrow is a holiday.'],
            json_decode(DB::table('notifications')->where('type', 'promo')->value('data'), true),
        );

        $sent = SchoolPushNotification::sole();
        $this->assertSame(8, (int) $sent->organization_id);
        $this->assertSame('students', $sent->audience);
        $this->assertSame('Class 6 · Section A', $sent->audience_label);
        $this->assertSame([101], $sent->user_ids);
        $this->assertSame(1, $sent->recipient_count);
        $this->assertSame(2, $sent->device_count);
        $this->assertSame(1, $sent->web_count);
        $this->assertSame(['android' => 1, 'ios' => 1], $sent->device_breakdown);
        $this->assertTrue($sent->delivered);
        $this->assertSame(500, (int) $sent->sent_by);
    }

    public function test_everyone_is_only_this_schools_students_and_teachers(): void
    {
        $this->compose('everyone')->call('review')->assertSet('previewRecipients', 5)->call('send');
        $ids = $this->pushed[0][0];
        sort($ids);
        $this->assertSame([101, 102, 103, 201, 202], $ids);

        $this->pushed = [];
        $this->compose('teachers')->call('review')->assertSet('previewRecipients', 2)->call('send');
        $this->assertSame([201, 202], $this->pushed[0][0]);
        $this->assertSame('All teachers', SchoolPushNotification::latest('id')->first()->audience_label);
    }

    public function test_anyone_of_the_school_can_be_picked_by_name_and_no_one_else(): void
    {
        $page = $this->compose('selected')
            ->call('review')
            ->assertHasErrors('audience')
            ->set('search', 'priya')
            ->assertSee('Priya Verma')
            ->assertDontSee('Priya Other')
            ->assertDontSee('Priya Elsewhere')
            ->set('search', 'E-1')
            ->assertSee('Meena Teacher')
            ->call('pick', 's4')      // another school's student
            ->call('pick', 't3')      // another school's teacher
            ->assertSet('picked', [])
            ->call('pick', 's2')
            ->call('pick', 't1')
            ->call('pick', 's2')      // twice is once
            ->assertSet('picked', ['s2', 't1'])
            ->assertSet('search', '')
            ->call('unpick', 't1')
            ->call('pick', 't1')
            ->call('review')
            ->assertHasNoErrors()
            ->assertSet('previewRecipients', 2)
            ->assertSet('previewDevices', 1)
            ->call('send');

        $this->assertSame([[102, 201], 'promo', ['title' => 'School closed', 'body' => 'Tomorrow is a holiday.']], $this->pushed[0]);
        $this->assertSame('Priya Verma, Meena Teacher', SchoolPushNotification::sole()->audience_label);
        $page->assertSet('picked', [])->assertSet('audience', 'students');
    }

    public function test_another_schools_class_is_refused(): void
    {
        $this->compose()->set('standardId', '3')->call('review')->assertHasErrors('standardId')->assertSet('confirming', false);
        $this->compose()->set('standardId', '2')->set('sectionId', '1')->call('review')->assertHasErrors('sectionId');
        $this->compose()->set('title', '')->call('review')->assertHasErrors('title');
        $this->assertSame([], $this->pushed);
    }

    public function test_delete_takes_it_out_of_the_bells_it_went_to_only(): void
    {
        $this->compose()->set('standardId', '1')->call('review')->call('send');   // Aarav and Priya
        $sent = SchoolPushNotification::sole();
        $this->assertSame([101, 102], $sent->user_ids);

        // Same words, sent to another school's student by someone else.
        DB::table('notifications')->insert([
            'id' => 'x-1', 'type' => 'promo', 'notifiable_type' => User::class, 'notifiable_id' => 104,
            'data' => json_encode(['type' => 'promo', 'title' => 'School closed', 'body' => 'Tomorrow is a holiday.']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::test(PushNotification::class)
            ->call('viewCampaign', $sent->id)
            ->assertSee('Notification Details')
            ->call('confirmDelete', $sent->id)
            ->assertSet('deleteFromBells', true)
            ->call('deleteCampaign');

        $this->assertSame(0, SchoolPushNotification::count());
        $this->assertSame([104], DB::table('notifications')->where('type', 'promo')->pluck('notifiable_id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_another_schools_notifications_are_not_seen_or_deleted(): void
    {
        $theirs = SchoolPushNotification::create([
            'organization_id' => 9, 'title' => 'Their secret', 'body' => 'Not yours.', 'audience' => 'students', 'user_ids' => [104],
        ]);

        Livewire::test(PushNotification::class)
            ->assertDontSee('Their secret')
            ->call('viewCampaign', $theirs->id)
            ->assertDontSee('Not yours.')
            ->call('confirmDelete', $theirs->id)
            ->call('deleteCampaign');

        $this->assertNotNull($theirs->fresh());
    }

    public function test_a_school_with_the_notification_module_off_has_no_tile(): void
    {
        DB::table('module_organization')->insert(['organization_id' => 8, 'module_key' => 'notification', 'enabled' => false]);

        $routes = array_column(Livewire::test(More::class)->get('items'), 'route');
        $this->assertNotContains('admin.push-notification', $routes);
        $this->assertContains('admin.messaging', $routes);
    }
}
