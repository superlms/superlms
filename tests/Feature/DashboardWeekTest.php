<?php

namespace Tests\Feature;

use App\Livewire\Admin\Home;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Student\StudentAttendance;
use App\Models\Teacher\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The dashboard's week table and today's attendance tiles read the last seven
 * days in one grouped query each; every figure must be what the old per-day
 * counts gave — the school's own students and teachers, present 1 / absent 0,
 * a deleted student's or another school's marks left out, and every fee
 * payment of the school (0 on a day without one).
 */
class DashboardWeekTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 11:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        // The rest of loadStatistics' reads, left empty.
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->string('fee_type')->default('academic'); $t->decimal('amount', 10, 2)->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->decimal('monthly_fee', 10, 2)->default(0); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id');
            $t->unsignedBigInteger('student_detail_id'); $t->text('billable_months')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->decimal('amount', 10, 2)->default(0);
            $t->date('payment_date')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->date('attendance_date')->nullable(); $t->integer('status'); $t->timestamps();
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('teacher_detail_id');
            $t->date('attendance_date')->nullable(); $t->integer('status'); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type')->default('academic');
            $t->decimal('amount', 10, 2)->default(0); $t->dateTime('payment_date')->nullable(); $t->timestamps();
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_week_and_today_figures_match_the_per_day_counts(): void
    {
        mt_srand(11);
        $mine = $other = $teachers = [];
        for ($i = 0; $i < 30; $i++) {
            $mine[] = DB::table('student_details')->insertGetId(['organization_id' => $this->org]);
            $other[] = DB::table('student_details')->insertGetId(['organization_id' => 99]);
        }
        for ($i = 0; $i < 8; $i++) {
            $teachers[] = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org]);
        }

        // Ten days of marks with every status, a deleted student (id 9999) and
        // marks of this school's students filed under another school.
        foreach (array_merge($mine, $other, [9999]) as $sid) {
            for ($d = 0; $d < 10; $d++) {
                DB::table('student_attendances')->insert([
                    'organization_id' => mt_rand(0, 9) ? $this->org : 99, 'student_detail_id' => $sid,
                    'attendance_date' => now()->subDays($d)->toDateString(), 'status' => mt_rand(0, 4),
                ]);
            }
        }
        foreach (array_merge($teachers, [9999]) as $tid) {
            for ($d = 0; $d < 10; $d++) {
                DB::table('teacher_attendances')->insert([
                    'organization_id' => $this->org, 'teacher_detail_id' => $tid,
                    'attendance_date' => now()->subDays($d)->toDateString(), 'status' => mt_rand(0, 4),
                ]);
            }
        }
        // Fees on some days only, with times of day, nought amounts and another school's.
        foreach ([0, 0, 1, 3, 3, 3, 6, 8] as $k => $d) {
            DB::table('fee_payments')->insert([
                'organization_id' => $k === 7 ? 99 : $this->org, 'amount' => [0, 500, 1250.5][$k % 3],
                'payment_date' => now()->subDays($d)->toDateString() . ($k % 2 ? ' 10:15:00' : ' 00:00:00'),
            ]);
        }

        $old = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $old[] = [
                'sp' => StudentAttendance::whereHas('studentDetail', fn ($q) => $q->forOrganization())->whereDate('attendance_date', $date)->where('status', true)->count(),
                'sa' => StudentAttendance::whereHas('studentDetail', fn ($q) => $q->forOrganization())->whereDate('attendance_date', $date)->where('status', false)->count(),
                'tp' => TeacherAttendance::whereHas('teacherDetail', fn ($q) => $q->forOrganization())->whereDate('attendance_date', $date)->where('status', true)->count(),
                'ta' => TeacherAttendance::whereHas('teacherDetail', fn ($q) => $q->forOrganization())->whereDate('attendance_date', $date)->where('status', false)->count(),
                'fee' => FeePayment::where('organization_id', $this->org)->whereDate('payment_date', $date)->sum('amount'),
            ];
        }

        $home = new Home();
        (fn () => $this->loadStatistics())->call($home);
        (fn () => $this->loadLast7DaysData())->call($home);

        $this->assertCount(7, $home->last7DaysData);
        foreach ($old as $k => $o) {
            $row = $home->last7DaysData[$k];
            $this->assertSame(now()->subDays(6 - $k)->format('Y-m-d'), $row['date']);
            $this->assertSame($o['sp'], $row['student_present']);
            $this->assertSame($o['sa'], $row['student_absent']);
            $this->assertSame($o['sp'] + $o['sa'], $row['student_total']);
            $this->assertSame($o['tp'], $row['teacher_present']);
            $this->assertSame($o['ta'], $row['teacher_absent']);
            $this->assertSame($o['tp'] + $o['ta'], $row['teacher_total']);
            $this->assertEquals($o['fee'], $row['fee_collected']);
        }
        $this->assertSame(0, $home->last7DaysData[1]['fee_collected']); // 5 days ago: no payment

        $today = end($old);
        $this->assertSame($today['sp'], $home->studentsPresentToday);
        $this->assertSame($today['sa'], $home->studentsAbsentToday);
        $this->assertSame($today['tp'], $home->teachersPresentToday);
        $this->assertSame($today['ta'], $home->teachersAbsentToday);
        $this->assertGreaterThan(0, $home->studentsPresentToday);
    }
}
