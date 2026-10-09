<?php

namespace Tests\Feature;

use App\Livewire\Admin\More;
use App\Livewire\Admin\RulesAndRegulation;
use App\Livewire\Admin\Users;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Users in the Students list's look (status dot in the actions, still a switch;
 * icon actions; access as plain text), its Add / Edit form in the Students
 * form's look and its View as the Students view (access as text with dots); Rules & Regulations with its tabs under the heading; and
 * no Credit tile on More.
 */
class UsersRulesMoreTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('u', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('logo')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->string('image')->nullable();
            $t->string('mobile_number')->nullable(); $t->string('alternative_mobile')->nullable(); $t->text('permissions')->nullable();
            $t->boolean('is_active')->default(true); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Demo School']);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    public function test_users_list_and_form_in_the_new_look(): void
    {
        $sub = User::forceCreate(['name' => 'Ravi Clerk', 'email' => 'ravi@x.in', 'role' => 'sub-admin', 'organization_id' => $this->org,
            'mobile_number' => '9999999999', 'permissions' => ['admin.student', 'admin.fee'], 'is_active' => true]);

        $page = Livewire::test(Users::class);
        $html = $page->html();
        $this->assertStringContainsString('>S.No</th>', $html);
        $this->assertStringContainsString('<span class="text-sm text-gray-700">2 functionalities</span>', $html);
        $this->assertStringContainsString('wire:click="toggleStatus(' . $sub->id . ')"', $html);
        $this->assertStringContainsString('bg-green-500', $html);
        $this->assertStringContainsString('class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"', $html);
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-500', $html);   // no boxed buttons
        $this->assertStringNotContainsString('rounded-full bg-indigo-50 text-indigo-600', $html);          // no chips

        // The dot still switches the status.
        $page->call('toggleStatus', $sub->id);
        $this->assertFalse((bool) DB::table('users')->where('id', $sub->id)->value('is_active'));

        // View, as the Students view: label / value rows, the screens granted as plain text with dots.
        $catalog = $page->instance()->permissionCatalog();
        $html = $page->call('view', $sub->id)->html();
        $this->assertStringContainsString('grid grid-cols-3 gap-3 text-sm', $html);
        $this->assertStringContainsString($catalog['admin.student'] . ' · ' . $catalog['admin.fee'], $html);
        $this->assertStringNotContainsString('rounded-full bg-indigo-50 text-indigo-700', $html);       // no chips
        // Its Edit closes the view and opens the form on the same user.
        $page->call('editFromView', $sub->id);
        $this->assertFalse($page->get('showViewPanel'));
        $this->assertTrue($page->get('showPanel'));
        $this->assertSame($sub->id, $page->get('editId'));
        $page->call('closePanel');

        // Add New User: the Students form — a photo row, then a flat two-column grid of labelled boxes.
        $html = $page->call('openCreate')->html();
        $this->assertStringContainsString('absolute top-0 right-0 bottom-0 w-full max-w-3xl', $html);
        $this->assertStringContainsString('Step 1 of 2', $html);
        $this->assertStringContainsString('<label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name', $html);
        $this->assertStringContainsString('grid grid-cols-1 sm:grid-cols-2 gap-3', $html);
        $this->assertStringContainsString('wire:model="fullName"', $html);
        $html = $page->set('fullName', 'Sita')->set('email', 'sita@x.in')->set('mobile', '9876543210')->set('gender', 'female')
            ->call('nextStep')->html();
        $this->assertSame(2, $page->get('step'));
        $this->assertStringContainsString('(0 of ' . count($catalog) . ' selected)', $html);
        $this->assertStringContainsString('wire:click="selectAllPermissions"', $html);
    }

    public function test_rules_tabs_sit_under_the_heading(): void
    {
        Schema::create('rules_and_regulations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->text('content')->nullable(); $t->timestamps();
        });

        $html = Livewire::test(RulesAndRegulation::class)->html();
        $heading = strpos($html, 'Rules & Regulations</h1>');
        $tabs    = strpos($html, "wire:click=\"showTab('view')\"");
        $this->assertNotFalse($heading);
        $this->assertNotFalse($tabs);
        $this->assertLessThan($tabs, $heading, 'the heading comes first, the tabs under it');
        $this->assertSame(1, substr_count($html, 'Rules & Regulations</h1>'));
    }

    public function test_more_has_no_credit_tile(): void
    {
        $items = collect(Livewire::test(More::class)->get('items'))->pluck('route');
        $this->assertNotContains('admin.credit', $items);
        $this->assertContains('admin.rules-and-regulation', $items);
    }
}
