<?php

namespace Tests\Feature;

use App\Livewire\Chat\Messenger;
use App\Livewire\Chat\Notifier;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Messages (2026-11-26): the school admins' chat, with the
 * platform in it. The Super Admin chats with every school's admin and the sub
 * super admins; a sub super admin with the Super Admin and the other sub
 * super admins; a school's admin also has the Super Admin in their list.
 */
class SuperAdminChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('c', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('logo')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->string('image')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->text('permissions')->nullable(); $t->boolean('is_active')->default(true); $t->string('mobile_number')->nullable(); $t->timestamps();
        });
        foreach (['2026_05_22_120000_create_chat_tables', '2026_09_10_000001_extend_chat_messaging',
                  '2026_09_15_000001_create_chat_blocks_table', '2026_09_16_000001_add_attachment_received_at_to_chat_messages'] as $m) {
            (require base_path("database/migrations/{$m}.php"))->up();
        }

        DB::table('organizations')->insert([['id' => 1, 'name' => 'Adarsh Public School'], ['id' => 2, 'name' => 'Sunrise Academy']]);
        DB::table('users')->insert([
            ['id' => 1,  'name' => 'Amit Dagur',   'role' => 'super-admin',     'organization_id' => null, 'is_active' => 1],
            ['id' => 2,  'name' => 'Amit (other)', 'role' => 'super-admin',     'organization_id' => null, 'is_active' => 1],
            ['id' => 3,  'name' => 'Neha Sub',     'role' => 'sub-super-admin', 'organization_id' => null, 'is_active' => 1],
            ['id' => 4,  'name' => 'Ravi Sub',     'role' => 'sub-super-admin', 'organization_id' => null, 'is_active' => 1],
            ['id' => 10, 'name' => 'Adarsh Admin', 'role' => 'admin',           'organization_id' => 1,    'is_active' => 1],
            ['id' => 11, 'name' => 'Sunrise Admin','role' => 'admin',           'organization_id' => 2,    'is_active' => 1],
            ['id' => 12, 'name' => 'Adarsh Clerk', 'role' => 'sub-admin',       'organization_id' => 1,    'is_active' => 1],
            ['id' => 13, 'name' => 'Adarsh Books', 'role' => 'accounts',        'organization_id' => 1,    'is_active' => 1],
        ]);
    }

    private function text($page): string
    {
        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags($page->html())), ENT_QUOTES);
    }

    private function contactIds($page): array
    {
        return $page->viewData('contacts')->pluck('user.id')->sort()->values()->all();
    }

    public function test_the_super_admin_and_a_school_admin_chat_both_ways(): void
    {
        // The Super Admin: every school's admin and the sub super admins, each admin with their school.
        $this->actingAs(User::find(1));
        $sa = Livewire::test(Messenger::class);
        $this->assertSame([3, 4, 10, 11], $this->contactIds($sa));
        $seen = $this->text($sa);
        $this->assertStringContainsString("Schools' admins · Sub super admins", $seen);
        $this->assertStringContainsString('Admin · Adarsh Public School', $seen);
        $this->assertStringContainsString('Admin · Sunrise Academy', $seen);

        $sa->call('openChat', 10)->call('sendMessage', 'Hello Adarsh, your fee is due.');
        $conversation = Conversation::first();
        $this->assertSame(1, (int) $conversation->organization_id);   // the school's chat
        $this->assertSame(1, Message::count());

        // The school's admin has the Super Admin in their list, with the message unread.
        $this->actingAs(User::find(10));
        $admin = Livewire::test(Messenger::class);
        $this->assertContains(1, $this->contactIds($admin));
        $this->assertNotContains(2, $this->contactIds($admin));   // only the main super admin login …
        $row = $admin->viewData('contacts')->firstWhere('user.id', 1);
        $this->assertSame('Super Admin · SuperLMS', $row['role_label']);
        $this->assertSame(1, $row['unread']);
        $this->assertSame(1, Message::unreadCountFor(10));

        $admin->call('openChat', 1);
        $this->assertStringContainsString('Hello Adarsh, your fee is due.', $this->text($admin));
        $admin->call('sendMessage', 'Paying today.');
        $this->assertSame(1, Conversation::count());               // the same chat both ways

        $this->actingAs(User::find(1));
        $this->assertSame(1, Message::unreadCountFor(1));
        $this->assertStringContainsString('Paying today.', $this->text(Livewire::test(Messenger::class)->call('openChat', 10)));

        // A sub-admin or the accounts team of the school do not see the Super Admin.
        $this->actingAs(User::find(12));
        $this->assertNotContains(1, $this->contactIds(Livewire::test(Messenger::class)));
        $this->actingAs(User::find(13));
        $this->assertNotContains(1, $this->contactIds(Livewire::test(Messenger::class)));
    }

    public function test_another_super_admin_login_that_writes_shows_up_for_the_admin(): void
    {
        $this->actingAs(User::find(2));
        Livewire::test(Messenger::class)->call('openChat', 11)->call('sendMessage', 'Hi from the other login');

        $this->actingAs(User::find(11));
        $admin = Livewire::test(Messenger::class);
        $this->assertContains(2, $this->contactIds($admin));   // … plus any other login already in a chat with them
        $this->assertContains(1, $this->contactIds($admin));
    }

    public function test_a_sub_super_admin_chats_with_the_platform_only(): void
    {
        $this->actingAs(User::find(3));
        $sub = Livewire::test(Messenger::class);
        $this->assertSame([1, 2, 4], $this->contactIds($sub));
        $this->assertStringContainsString('Super Admin · Sub super admins', $this->text($sub));

        $sub->call('openChat', 1)->call('sendMessage', 'Report is ready');
        $this->assertSame(0, (int) Conversation::first()->organization_id);   // the platform's own chat

        // Not an admin: opening one is ignored.
        $sub->call('openChat', 10)->assertSet('selectedUserId', 1);

        // Messages is always open to a sub super admin, whatever they were granted.
        $this->assertTrue(User::find(3)->canAccessSuperAdminRoute('super-admin.messages'));
    }

    public function test_the_top_bar_and_the_notifier_lead_the_super_admin_to_messages(): void
    {
        $this->actingAs(User::find(10));
        Livewire::test(Messenger::class)->call('openChat', 1)->call('sendMessage', 'Need help');

        $this->actingAs(User::find(1));
        $notifier = Livewire::test(Notifier::class)->assertSet('unread', 1);
        $this->assertStringContainsString(route('super-admin.messages'), $notifier->html());

        $ql = Livewire::test(\App\Livewire\SuperAdmin\QuickLinks::class);
        $this->assertStringContainsString('href="' . route('super-admin.messages') . '" class="sk-btn"', $ql->html());
        $this->assertStringContainsString('sk-actions sk-plain', $ql->html());
        $this->assertSame(1, $ql->viewData('unreadMessages'));
    }
}
