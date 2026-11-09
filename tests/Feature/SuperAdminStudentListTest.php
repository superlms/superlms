<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Students in the product-list look: three cards (Total,
 * Active, Inactive) with their last seven days as bars, the table's column
 * headers sorting it, and the ticked rows deleted together after asking.
 */
class SuperAdminStudentListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);
        Carbon::setTestNow('2026-11-09 10:00:00');

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->boolean('is_active')->default(true);
            $t->string('image')->nullable(); $t->text('permissions')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id')->nullable(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('admission_no')->nullable(); $t->string('roll_no')->nullable();
            $t->string('phone')->nullable(); $t->string('father_name')->nullable(); $t->string('gender')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => 9, 'name' => 'Doon School']);
        DB::table('school_infos')->insert(['organization_id' => 9]);
        DB::table('standards')->insert(['id' => 5, 'organization_id' => 9, 'name' => '5th']);
        DB::table('sections')->insert(['id' => 7, 'standard_id' => 5, 'name' => 'A']);

        // Two added today, one three days ago (switched off), one ten days ago.
        $this->student(1, 'Aarav Mehta', 'A-102', true, '2026-11-09 08:00:00');
        $this->student(2, 'Zoya Khan', 'A-101', true, '2026-11-09 09:00:00');
        $this->student(3, 'Kabir Singh', 'A-103', false, '2026-11-06 09:00:00');
        $this->student(4, 'Meera Iyer', 'A-104', true, '2026-10-30 09:00:00');

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

    private function student(int $id, string $name, string $admission, bool $active, string $at): void
    {
        DB::table('users')->insert(['id' => 100 + $id, 'name' => $name, 'email' => "s{$id}@doon.in", 'role' => 'student',
            'organization_id' => 9, 'is_active' => $active, 'created_at' => $at, 'updated_at' => $at]);
        DB::table('student_details')->insert(['id' => $id, 'user_id' => 100 + $id, 'organization_id' => 9, 'full_name' => $name,
            'admission_no' => $admission, 'roll_no' => (string) $id, 'phone' => '98765432' . $id . '0', 'gender' => 'male',
            'standard_id' => 5, 'section_id' => 7, 'created_at' => $at, 'updated_at' => $at]);
    }

    /** @return list<string> the table's names, top to bottom */
    private static function names(string $html): array
    {
        preg_match_all('/<p class="font-medium text-gray-900 truncate leading-tight">([^<]+)<\/p>/', $html, $m);

        return array_map('trim', $m[1]);
    }

    public function test_the_cards_show_each_figure_with_its_week(): void
    {
        $page = Livewire::test(Student::class);
        $cards = $page->viewData('cards');

        $this->assertSame(['Total Students', 'Active Students', 'Inactive Students'], array_column($cards, 'title'));
        $this->assertSame([4, 3, 1], array_column($cards, 'value'));
        $this->assertSame('+3 this week', $cards[0]['badge']);
        $this->assertSame('1 added the week before', $cards[0]['note']);
        $this->assertSame(['75%', '25%'], [$cards[1]['badge'], $cards[2]['badge']]);

        // Seven bars, today last: two added today (both active), one three days ago (inactive).
        $this->assertSame([0, 0, 0, 1, 0, 0, 2], array_column($cards[0]['bars'], 'count'));
        $this->assertSame([0, 0, 0, 0, 0, 0, 2], array_column($cards[1]['bars'], 'count'));
        $this->assertSame([0, 0, 0, 1, 0, 0, 0], array_column($cards[2]['bars'], 'count'));

        $page->assertSee('Add New Student')->assertSee('Class 5th-A · Roll 1');
    }

    public function test_the_headers_sort_the_list(): void
    {
        $page = Livewire::test(Student::class);
        $this->assertSame(['Aarav Mehta', 'Kabir Singh', 'Meera Iyer', 'Zoya Khan'], self::names($page->html()));

        // Unsorted is A–Z by name: Student's first click is Z–A.
        $page->call('sort', 'name');
        $this->assertSame(['Zoya Khan', 'Meera Iyer', 'Kabir Singh', 'Aarav Mehta'], self::names($page->html()));

        $page->call('sort', 'admission');
        $this->assertSame(['Zoya Khan', 'Aarav Mehta', 'Kabir Singh', 'Meera Iyer'], self::names($page->html()));

        // Anything not a header is ignored.
        $page->call('sort', 'password')->assertSet('sortBy', 'admission');
    }

    public function test_ticked_students_are_deleted_together_after_asking(): void
    {
        $page = Livewire::test(Student::class)
            ->call('confirmBulkDelete', [1, 3, 3, 'x'])
            ->assertSet('showBulkDeleteConfirm', true)
            ->assertSet('bulkDeleteIds', [1, 3])
            ->assertSee('Delete 2 students?');

        $this->assertSame(4, DB::table('student_details')->count());

        $page->call('doBulkDelete')
            ->assertSet('showBulkDeleteConfirm', false)
            ->assertDispatched('sa-students-deleted');

        $this->assertSame([2, 4], DB::table('student_details')->orderBy('id')->pluck('id')->all());
        // Their logins go with them.
        $this->assertSame([102, 104], DB::table('users')->orderBy('id')->pluck('id')->all());
    }

    public function test_cancel_deletes_nothing(): void
    {
        Livewire::test(Student::class)
            ->call('confirmBulkDelete', [1, 2])
            ->call('cancelBulkDelete')
            ->assertSet('showBulkDeleteConfirm', false)
            ->assertSet('bulkDeleteIds', []);

        $this->assertSame(4, DB::table('student_details')->count());
    }
}
