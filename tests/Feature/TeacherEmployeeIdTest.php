<?php

namespace Tests\Feature;

use App\Support\EmployeeIds;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * User's ask of 2026-11-25: a teacher's Employee ID is theirs alone in the
 * school — Add / Edit Teacher (panel, admin app, Super Admin) turn down one
 * another teacher of the school already has, whatever its case or spaces.
 */
class TeacherEmployeeIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id'); $t->string('employee_id')->nullable(); $t->timestamps();
        });

        $user = fn (string $name, int $org) => DB::table('users')->insertGetId(['name' => $name, 'role' => 'teacher', 'organization_id' => $org]);
        DB::table('teacher_details')->insert([
            ['user_id' => $user('Neha Verma', 1), 'organization_id' => 1, 'employee_id' => 'EMP001'],
            ['user_id' => $user('Ravi Gupta', 1), 'organization_id' => 1, 'employee_id' => 'EMP002'],
            ['user_id' => $user('Other School', 2), 'organization_id' => 2, 'employee_id' => 'EMP003'],
            // Two who shared an ID before the rule.
            ['user_id' => $user('Old One', 1), 'organization_id' => 1, 'employee_id' => 'T-9'],
            ['user_id' => $user('Old Two', 1), 'organization_id' => 1, 'employee_id' => 'T-9'],
        ]);
    }

    public function test_an_employee_id_belongs_to_one_teacher_of_the_school(): void
    {
        $neha = (int) DB::table('teacher_details')->where('employee_id', 'EMP001')->value('user_id');
        $ravi = (int) DB::table('teacher_details')->where('employee_id', 'EMP002')->value('user_id');
        $old2 = (int) DB::table('teacher_details')->where('employee_id', 'T-9')->orderByDesc('id')->value('user_id');

        // A new teacher: another's ID is turned down, whatever its case or spaces.
        $this->assertSame('This Employee ID is already given to Neha Verma. Each teacher needs their own Employee ID.',
            EmployeeIds::problem(1, 'EMP001'));
        $this->assertNotNull(EmployeeIds::problem(1, ' emp001 '));
        $this->assertNull(EmployeeIds::problem(1, 'EMP004'));
        $this->assertNull(EmployeeIds::problem(1, 'EMP003'));          // another school's
        $this->assertNull(EmployeeIds::problem(1, ''));

        // Editing: a teacher keeps their own ID; another teacher's is turned down.
        $this->assertNull(EmployeeIds::problem(1, 'EMP001', $neha));
        $this->assertNull(EmployeeIds::problem(1, 'emp001', $neha));
        $this->assertSame('This Employee ID is already given to Neha Verma. Each teacher needs their own Employee ID.',
            EmployeeIds::problem(1, 'EMP001', $ravi));

        // Two who shared one before: each can still be saved as they are, but no one new takes it.
        $this->assertNull(EmployeeIds::problem(1, 'T-9', $old2));
        $this->assertNotNull(EmployeeIds::problem(1, 'T-9'));
        $this->assertNotNull(EmployeeIds::problem(1, 'T-9', $ravi));
    }
}
