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
 * Super Admin → Students with the Fees page's header: its figures in one line
 * beside Add (the cards are gone), the Fees filter bar, and a table with S.No
 * in place of tick boxes, the class alone under the name, the status as a dot
 * beside the row's ⋮, and column headers that sort it.
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
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('name')->nullable();
            $t->integer('order')->nullable(); $t->timestamps();
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

    public function test_the_header_carries_the_figures_in_the_fees_look(): void
    {
        $page = Livewire::test(Student::class);

        // Schools, students, active and inactive with their share, and the
        // ones added over the last seven days (two today, one three days ago).
        $this->assertSame([
            ['Schools', '1', 'text-gray-800', null],
            ['Students', '4', 'text-gray-800', null],
            ['Active', '3', 'text-emerald-600', '75%'],
            ['Inactive', '1', 'text-red-500', '25%'],
            ['This Week', '+3', 'text-blue-600', null],
        ], $page->viewData('stats'));

        $html = $page->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('<h1 class="text-lg sm:text-xl font-bold text-gray-900">Students</h1>', $html);
        $this->assertStringContainsString('Schools: 1 Students: 4 Active: 3 (75%) Inactive: 1 (25%) This Week: +3 Add New Student', $text);
        // The Fees filter bar, the search in it.
        $this->assertStringContainsString('Filter by:', $text);
        $this->assertMatchesRegularExpression('/Filter by:.*wire:model\.live\.debounce\.300ms="search"/s', $html);
        // No cards any more.
        $this->assertStringNotContainsString('Total Students', $text);

        // The filter bar a size smaller than Fees'; Export waits for a school, then is filled grey.
        $this->assertStringContainsString('class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2" data-filter-bar', $html);
        $this->assertMatchesRegularExpression('/wire:click="exportStudents" disabled[^>]*bg-gray-50 text-gray-300/s', $html);
        $page->set('filterOrganization', '9');
        $this->assertMatchesRegularExpression('/wire:click="exportStudents"[^>]*bg-gray-600 text-white/s', $page->html());

        // Filtered, the figures follow the list.
        $page->set('filterOrganization', '')->set('filterStatus', '0');
        $this->assertSame(['Students', '1'], array_slice($page->viewData('stats')[1], 0, 2));
    }

    public function test_email_comes_before_gender_then_school(): void
    {
        $html = Livewire::test(Student::class)->html();

        preg_match_all('/<th\b[^>]*>(?:\s*<button[^>]*>)?([^<]+)/', $html, $th);
        $this->assertSame(['S.No', 'Student', 'Admission No', 'Father\'s Name', 'Mobile', 'Email', 'Gender', 'School'],
            array_values(array_filter(array_map(fn ($h) => html_entity_decode(trim($h), ENT_QUOTES), $th[1]))));
        $this->assertStringContainsString('s1@doon.in Male Doon School', preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    public function test_rows_have_a_serial_number_the_class_alone_and_the_status_as_a_dot(): void
    {
        $html = Livewire::test(Student::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        // S.No in place of tick boxes, and no ticked-rows bar.
        $this->assertStringContainsString('>S.No</th>', $html);
        $this->assertStringNotContainsString('type="checkbox"', $html);
        $this->assertStringNotContainsString('Selected', $text);
        $this->assertStringContainsString('1 A Aarav Mehta Class 5th-A', $text);
        $this->assertStringContainsString('4 Z Zoya Khan Class 5th-A', $text);

        // The class under the name, without the roll number.
        $this->assertStringNotContainsString('Roll', $text);

        // No Status column: the status is a dot right beside the ⋮.
        $this->assertStringNotContainsString('>Status</th>', $html);
        $this->assertSame(3, substr_count($html, 'data-status="active"'));
        $this->assertSame(1, substr_count($html, 'data-status="inactive"'));
        $this->assertMatchesRegularExpression('/data-status="inactive"><\/span>\s*<button type="button" x-ref="dots" title="More"/', $html);

        // Bulk delete went with the tick boxes.
        foreach (['confirmBulkDelete', 'cancelBulkDelete', 'doBulkDelete'] as $gone) {
            $this->assertFalse(method_exists(Student::class, $gone), $gone);
        }
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
}
