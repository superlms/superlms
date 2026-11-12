<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Teachers in the Students list's look: the Fees page's header
 * with its figures in one line beside Add, the Fees filter bar, and a table
 * with S.No, the qualification under the name and the status as a dot
 * beside the row's ⋮ (View, Edit, Delete).
 */
class SuperAdminTeacherListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('t', 32))]);
        Carbon::setTestNow('2026-11-12 10:00:00');

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->boolean('is_active')->default(true);
            $t->string('mobile_number')->nullable(); $t->string('gender')->nullable();
            $t->string('image')->nullable(); $t->text('permissions')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('employee_id')->nullable(); $t->string('qualification')->nullable(); $t->string('phone')->nullable();
            $t->date('date_of_joining')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id')->nullable(); $t->unsignedBigInteger('subject_id')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert([['id' => 9, 'name' => 'Doon School'], ['id' => 10, 'name' => 'Green Valley']]);

        // Two added this week (one switched off), one a month ago.
        $this->teacher(1, 'Neha Sharma', 'M.Sc, B.Ed', true, '2026-11-10 09:00:00');
        $this->teacher(2, 'Arjun Rao', 'B.A', false, '2026-11-12 08:00:00');
        $this->teacher(3, 'Kavita Joshi', null, true, '2026-10-12 09:00:00');

        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 900;
        $user->role = 'super-admin';
        $user->permissions = [];
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function teacher(int $id, string $name, ?string $qualification, bool $active, string $at): void
    {
        DB::table('users')->insert(['id' => 200 + $id, 'name' => $name, 'email' => "t{$id}@doon.in", 'role' => 'teacher',
            'organization_id' => 9, 'is_active' => $active, 'mobile_number' => '91234567' . $id . '0', 'gender' => 'female',
            'created_at' => $at, 'updated_at' => $at]);
        DB::table('teacher_details')->insert(['id' => $id, 'user_id' => 200 + $id, 'organization_id' => 9, 'employee_id' => 'EMP-' . $id,
            'qualification' => $qualification, 'date_of_joining' => '2026-04-01', 'created_at' => $at, 'updated_at' => $at]);
    }

    public function test_the_header_carries_the_figures_in_the_fees_look(): void
    {
        $page = Livewire::test(Teacher::class);

        $this->assertSame([
            ['Schools', '2', 'text-gray-800', null],
            ['Teachers', '3', 'text-gray-800', null],
            ['Active', '2', 'text-emerald-600', '67%'],
            ['Inactive', '1', 'text-red-500', '33%'],
            ['This Week', '+2', 'text-blue-600', null],
        ], $page->viewData('stats'));

        $html = $page->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('<h1 class="text-lg sm:text-xl font-bold text-gray-900">Teachers</h1>', $html);
        $this->assertStringContainsString('Schools: 2 Teachers: 3 Active: 2 (67%) Inactive: 1 (33%) This Week: +2 Add New Teacher', $text);
        $this->assertMatchesRegularExpression('/Filter by:.*wire:model\.live\.debounce\.300ms="search"/s', $html);
        $this->assertStringContainsString('wire:click="exportTeachers"', $html);
    }

    public function test_rows_are_in_the_students_look(): void
    {
        $html = Livewire::test(Teacher::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        // A–Z by name, each with its serial number and the qualification under the name.
        preg_match_all('/<p class="font-medium text-gray-900 truncate leading-tight">([^<]+)<\/p>/', $html, $m);
        $this->assertSame(['Arjun Rao', 'Kavita Joshi', 'Neha Sharma'], array_map('trim', $m[1]));
        $this->assertStringContainsString('>S.No</th>', $html);
        $this->assertStringContainsString('1 A Arjun Rao B.A EMP-2', $text);
        $this->assertStringContainsString('3 N Neha Sharma M.Sc, B.Ed EMP-1', $text);
        $this->assertStringContainsString('01 Apr 2026', $text);

        // The status is a dot beside the ⋮; View, Edit and Delete are in its menu.
        $this->assertSame(2, substr_count($html, 'data-status="active"'));
        $this->assertSame(1, substr_count($html, 'data-status="inactive"'));
        $this->assertMatchesRegularExpression('/data-status="inactive"><\/span>\s*<button type="button" x-ref="dots" title="More"/', $html);
        foreach (['viewTeacher(202)', 'openEditPanel(2)', 'onDeleteTeacherSuperAdmin(2)'] as $call) {
            $this->assertStringContainsString('wire:click="' . $call . '"', $html);
        }
        $this->assertStringContainsString('Showing 1–3 of 3 entries', $text);
    }
}
