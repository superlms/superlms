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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Teacher Attendance → By Month: the month's dates down the side, a column per
 * teacher, each cell that teacher's status that day.
 */
class TeacherMonthGridTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-17 10:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
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

    private function teacher(string $name): int
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@t.test', 'organization_id' => $this->org]);

        return TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $this->org])->id;
    }

    private function mark(int $teacher, string $date, int $status): void
    {
        DB::table('teacher_attendances')->insert(['teacher_detail_id' => $teacher, 'organization_id' => $this->org, 'attendance_date' => $date, 'status' => $status]);
    }

    private function grid(string $pageClass, string $teacher = ''): ?array
    {
        $page = new $pageClass();
        $page->mount();
        $page->switchTeacherView('by_month');
        $page->tMonth = '2026-09';
        $page->tTeacherId = $teacher;

        return $page->render()->getData()['tMonthGrid'];
    }

    #[DataProvider('pages')]
    public function test_every_date_of_the_month_with_a_column_per_teacher(string $pageClass): void
    {
        $bina = $this->teacher('Bina');
        $asha = $this->teacher('Asha');
        $this->mark($asha, '2026-09-01', 1);
        $this->mark($asha, '2026-09-02', 0);
        $this->mark($bina, '2026-09-01', 2);
        $this->mark($bina, '2026-09-15', 3);
        $this->mark($asha, '2026-08-31', 0); // another month

        $grid = $this->grid($pageClass);

        $this->assertSame('September 2026', $grid['title']);
        $this->assertSame(['Asha', 'Bina'], array_column($grid['teachers'], 'name'));
        $this->assertCount(30, $grid['rows']);
        $this->assertSame('2026-09-01', $grid['rows'][0]['date']);
        $this->assertSame('2026-09-30', $grid['rows'][29]['date']);

        $cell = fn (string $date, int $teacher) => collect($grid['rows'])->firstWhere('date', $date)['cells'][$teacher];
        $this->assertSame('present', $cell('2026-09-01', $asha));
        $this->assertSame('half_day', $cell('2026-09-01', $bina));
        $this->assertSame('absent', $cell('2026-09-02', $asha));
        $this->assertSame('holiday', $cell('2026-09-15', $bina));
        $this->assertSame('not_marked', $cell('2026-09-03', $asha));
        $this->assertSame('holiday', $cell('2026-09-06', $asha));     // a Sunday
        $this->assertNull($cell('2026-09-18', $asha));                 // still to come
        $this->assertTrue(collect($grid['rows'])->firstWhere('date', '2026-09-17')['today']);

        $this->assertSame(1, $grid['totals'][$asha]['present']);
        $this->assertSame(1, $grid['totals'][$asha]['absent']);
        $this->assertSame(1, $grid['totals'][$bina]['half_day']);
    }

    #[DataProvider('pages')]
    public function test_picking_a_teacher_keeps_just_that_column(string $pageClass): void
    {
        $this->teacher('Asha');
        $bina = $this->teacher('Bina');

        $grid = $this->grid($pageClass, (string) $bina);

        $this->assertSame(['Bina'], array_column($grid['teachers'], 'name'));
        $this->assertSame([$bina], array_keys($grid['rows'][0]['cells']));
    }

    public function test_the_grid_renders(): void
    {
        $asha = $this->teacher('Asha');
        $this->mark($asha, '2026-09-01', 1);

        \Livewire\Livewire::test(AdminAttendancePage::class)
            ->call('switchTeacherView', 'by_month')
            ->set('tMonth', '2026-09')
            ->assertSee('Teacher Attendance · September 2026')
            ->assertSee('01 Sep')
            ->assertSee('30 Sep')
            ->assertSee('Asha')
            ->assertSee('P 1');
    }
}
