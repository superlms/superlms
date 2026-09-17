<?php

namespace Tests\Feature;

use App\Livewire\Admin\Attendance as AttendancePage;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The date box in the admin's Mark Teacher Attendance panel: the panel is not
 * rebuilt when the date changes (which closed the calendar and dropped a date
 * being typed), and only a real day is ever loaded.
 */
class TeacherMarkDateTest extends TestCase
{
    private int $org = 5;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->string('role')->nullable();
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

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    private function teacher(): TeacherDetail
    {
        $user = User::create(['name' => 'Kushal', 'email' => 'k@example.com', 'organization_id' => $this->org]);

        return TeacherDetail::create(['user_id' => $user->id, 'organization_id' => $this->org]);
    }

    public function test_picking_a_date_loads_that_day(): void
    {
        $t = $this->teacher();
        TeacherAttendance::create(['teacher_detail_id' => $t->id, 'organization_id' => $this->org, 'attendance_date' => '2026-09-10', 'status' => 0]);

        $page = new AttendancePage();
        $page->mount();
        $page->openTeacherMark();

        $page->tMarkDate = '2026-09-10';
        $page->updatedTMarkDate();

        $this->assertSame('2026-09-10', $page->tMarkDate);
        $this->assertTrue($page->teacherMarkExisting);
        $this->assertSame('absent', $page->teacherMark[$t->id]['status']);
    }

    public function test_a_date_that_is_not_a_real_day_falls_back_to_today(): void
    {
        $this->teacher();
        $page = new AttendancePage();
        $page->mount();
        $page->openTeacherMark();

        foreach (['', '2026-02-30', '2026-9-1', 'abc'] as $bad) {
            $page->tMarkDate = $bad;
            $page->updatedTMarkDate();
            $this->assertSame(now()->toDateString(), $page->tMarkDate, "for '{$bad}'");
        }
    }

    public function test_the_panel_is_not_keyed_on_the_date_and_the_box_is_left_alone(): void
    {
        $view = file_get_contents(resource_path('views/livewire/admin/attendance.blade.php'));

        $this->assertStringContainsString('wire:key="tmark-{{ count($teacherMark) }}"', $view);
        $this->assertStringNotContainsString('wire:model.live="tMarkDate"', $view);
        $this->assertStringContainsString('<input type="date" wire:ignore value="{{ $tMarkDate }}"', $view);

        // The view still compiles to valid PHP.
        $compiled = Blade::compileString($view);
        $file = tempnam(sys_get_temp_dir(), 'blade') . '.php';
        file_put_contents($file, $compiled);
        exec('php -l ' . escapeshellarg($file), $out, $code);
        @unlink($file);
        $this->assertSame(0, $code, implode("\n", $out));
    }
}
