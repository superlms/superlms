<?php

namespace Tests\Feature;

use App\Livewire\Accounts\Attendance as AccountsAttendancePage;
use App\Livewire\Admin\Attendance as AdminAttendancePage;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Attendance → By Date, for the teachers and for a class's students: two
 * columns filled across (1 left, 2 right, 3 left, …), each row its number,
 * photo, name over the login, status and remark. The status is plain text, an
 * absent one red, and the card's header is not coloured.
 */
class TeacherDayListTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-17 10:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('teacher_detail_id');
            $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date');
            $t->integer('status');
            $t->string('remarks')->nullable();
            $t->unsignedBigInteger('marked_by')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date');
            $t->integer('status');
            $t->string('remarks')->nullable();
            $t->unsignedBigInteger('marked_by')->nullable();
            $t->timestamps();
        });
        foreach (['standards', 'sections', 'assign_teacher_standards'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->unsignedBigInteger('organization_id')->nullable();
                if ($table === 'standards') { $t->string('name'); $t->integer('order')->default(0); }
                if ($table === 'sections') { $t->unsignedBigInteger('standard_id'); $t->string('name'); }
                if ($table === 'assign_teacher_standards') {
                    $t->unsignedBigInteger('teacher_detail_id');
                    $t->unsignedBigInteger('standard_id');
                    $t->unsignedBigInteger('section_id')->default(0);
                }
                $t->timestamps();
            });
        }

        config(['app.key' => 'base64:' . base64_encode(str_repeat('g', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 500;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function pages(): array
    {
        return ['admin' => [AdminAttendancePage::class], 'accounts' => [AccountsAttendancePage::class]];
    }

    private function teacher(string $name, ?string $username): int
    {
        $user = User::create(['name' => $name, 'username' => $username, 'email' => strtolower($name) . '@t.test', 'organization_id' => $this->org]);

        return TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $this->org])->id;
    }

    private function mark(int $teacher, int $status, ?string $remark = null): void
    {
        DB::table('teacher_attendances')->insert([
            'teacher_detail_id' => $teacher, 'organization_id' => $this->org,
            'attendance_date' => '2026-09-16', 'status' => $status, 'remarks' => $remark,
        ]);
    }

    /** A student of class 1, section 1. */
    private function student(string $name, ?string $admissionNo, ?int $status = null): int
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@s.test', 'organization_id' => $this->org]);
        $id = DB::table('student_details')->insertGetId([
            'user_id' => $user->id, 'organization_id' => $this->org, 'standard_id' => 1, 'section_id' => 1,
            'full_name' => $name, 'admission_no' => $admissionNo,
        ]);
        if ($status !== null) {
            DB::table('student_attendances')->insert([
                'student_detail_id' => $id, 'organization_id' => $this->org, 'attendance_date' => '2026-09-16', 'status' => $status,
            ]);
        }

        return $id;
    }

    /** The day's rows as the page hands them to its view. */
    private function rows(string $pageClass): array
    {
        $page = new $pageClass();
        $page->mount();
        $page->tDate = '2026-09-16';

        return $page->render()->getData()['tByDateRows']->all();
    }

    /** The list's rows in the order they are written: [number, name, login line, status]. */
    private function listed(string $html, string $key): array
    {
        $rows = [];
        foreach (array_slice(explode('wire:key="' . $key . '-', $html), 1) as $chunk) {
            $text = trim(preg_replace('/\s+/', ' ', strip_tags(substr($chunk, strpos($chunk, '>') + 1, 1400))));
            $rows[] = $text;
        }

        return $rows;
    }

    #[DataProvider('pages')]
    public function test_a_row_carries_the_username_beside_what_it_had(string $pageClass): void
    {
        $asha = $this->teacher('Asha', 'asha01');
        $this->teacher('Bina', null);
        $this->mark($asha, 0, 'On leave');

        $rows = $this->rows($pageClass);

        $this->assertSame(['Asha', 'Bina'], array_column($rows, 'name'));
        $this->assertSame(['asha01', ''], array_column($rows, 'username'));
        $this->assertSame(['asha@t.test', 'bina@t.test'], array_column($rows, 'email'));
        $this->assertSame(['absent', 'not_marked'], array_column($rows, 'status'));
        $this->assertSame('On leave', $rows[0]['remark']);
    }

    #[DataProvider('pages')]
    public function test_the_teachers_run_across_two_columns_as_plain_statuses(string $pageClass): void
    {
        $asha = $this->teacher('Asha', 'asha01');
        $bina = $this->teacher('Bina', 'bina02');
        $this->teacher('Chitra', null);
        $this->mark($asha, 1);
        $this->mark($bina, 0, 'On leave');

        $html = Livewire::test($pageClass)->set('tDate', '2026-09-16')->html();
        $list = $this->listed($html, 't-daylist');

        // One grid of two columns, written in order — so 1 is on the left, 2
        // on the right, 3 on the left again — and the odd place is filled so
        // the line between the columns runs to the foot.
        $this->assertStringContainsString('lg:grid-cols-2', $html);
        $this->assertCount(3, $list);
        $this->assertStringStartsWith('1 A Asha asha01 Present', $list[0]);
        $this->assertStringStartsWith('2 B Bina bina02 Absent On leave', $list[1]);
        $this->assertStringStartsWith('3 C Chitra chitra@t.test', $list[2]);   // no username: the email
        $this->assertStringContainsString('wire:key="t-daylist-1" class="grid grid-cols-[3rem_minmax(0,1.5fr)_6rem_minmax(0,1fr)] items-center border-t border-gray-100 lg:border-l lg:border-l-gray-200"', $html);
        $this->assertStringContainsString('<div class="hidden lg:block border-t border-gray-100 lg:border-l lg:border-l-gray-200"></div>', $html);
        $this->assertStringNotContainsString('asha@t.test', $html);

        // Plain text, the absent one red — no coloured tag.
        $this->assertMatchesRegularExpression('/text-red-600 font-medium">\s*Absent\s*</', $html);
        $this->assertMatchesRegularExpression('/text-gray-700">\s*Present\s*</', $html);
        $this->assertStringNotContainsString('rounded-full bg-red-100', $html);
        $this->assertStringNotContainsString('rounded-full bg-emerald-100', $html);

        // The card's header is not coloured.
        $this->assertStringContainsString('Teacher Attendance · Wednesday, 16 Sep 2026', $html);
        $this->assertStringNotContainsString('from-blue-50 to-indigo-50', $html);
    }

    #[DataProvider('pages')]
    public function test_a_single_row_stays_one_column(string $pageClass): void
    {
        $asha = $this->teacher('Asha', 'asha01');
        $this->teacher('Bina', 'bina02');
        $this->mark($asha, 0);

        $html = Livewire::test($pageClass)->set('tDate', '2026-09-16')->set('tByDateStatus', 'absent')->html();

        $this->assertCount(1, $this->listed($html, 't-daylist'));
        $this->assertStringNotContainsString('lg:grid-cols-2', $html);
    }

    #[DataProvider('pages')]
    public function test_no_teachers_says_so(string $pageClass): void
    {
        Livewire::test($pageClass)->set('tDate', '2026-09-16')->assertSee('No teachers found.');
    }

    #[DataProvider('pages')]
    public function test_the_students_of_a_class_read_the_same_way(string $pageClass): void
    {
        DB::table('standards')->insert(['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 5']);
        DB::table('sections')->insert(['id' => 1, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'A']);
        $this->student('Aman', 'ADM-1', 1);
        $this->student('Bhanu', 'ADM-2', 0);
        $this->student('Chetan', null);
        $this->student('Deepa', 'ADM-4', 1);

        $html = Livewire::test($pageClass)
            ->call('switchMainTab', 'student')
            ->set('stStandard', '1')->set('stSection', '1')->set('stDate', '2026-09-16')
            ->html();
        $list = $this->listed($html, 's-daylist');

        $this->assertCount(4, $list);
        $this->assertStringStartsWith('1 A Aman ADM-1 Present', $list[0]);
        $this->assertStringStartsWith('2 B Bhanu ADM-2 Absent', $list[1]);
        $this->assertStringStartsWith('3 C Chetan chetan@s.test', $list[2]);   // no admission number: the email
        $this->assertStringStartsWith('4 D Deepa ADM-4 Present', $list[3]);
        $this->assertStringContainsString('lg:grid-cols-2', $html);
        $this->assertStringNotContainsString('<div class="hidden lg:block border-t', $html);   // an even count: no filler

        $this->assertMatchesRegularExpression('/text-red-600 font-medium">\s*Absent\s*</', $html);
        $this->assertStringNotContainsString('rounded-full bg-red-100', $html);
        $this->assertStringContainsString('Student Attendance · Wednesday, 16 Sep 2026', $html);
        $this->assertStringNotContainsString('from-blue-50 to-indigo-50', $html);
    }

    public function test_the_day_header_is_coloured_unless_told_plain(): void
    {
        $stats = ['total' => 1, 'present' => 1, 'absent' => 0, 'half_day' => 0, 'holiday' => 0, 'not_marked' => 0];

        $coloured = view('livewire.admin._partials.attendance-dayheader', ['stats' => $stats, 'title' => 'Attendance'])->render();
        $plain = view('livewire.admin._partials.attendance-dayheader', ['stats' => $stats, 'title' => 'Attendance', 'plain' => true])->render();

        $this->assertStringContainsString('from-blue-50 to-indigo-50', $coloured);
        $this->assertStringNotContainsString('from-blue-50', $plain);
    }
}
