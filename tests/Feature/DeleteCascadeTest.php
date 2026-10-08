<?php

namespace Tests\Feature;

use App\Models\Admin\Exam;
use App\Models\Calendar\TimeTable;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Support\Cascade;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Whatever is deleted takes its own records with it (App\Support\Cascade):
 * a student their fees, attendance, marks and login's rows; a teacher their
 * own rows and payroll, not the marks or homework they entered for students;
 * a class its set-up and content, not the students' fees or results; an exam
 * its marks and datesheets; an event its class and place. Another student's,
 * teacher's, class's or exam's rows stay.
 */
class DeleteCascadeTest extends TestCase
{
    private function table(string $name, array $cols, bool $timestamps = true): void
    {
        Schema::create($name, function (Blueprint $t) use ($cols, $timestamps) {
            $t->id();
            foreach ($cols as $c) {
                $t->unsignedBigInteger($c)->nullable();
            }
            if ($timestamps) {
                $t->timestamps();
            }
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cascade::forgetSchema();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        $this->table('student_details', ['user_id', 'organization_id', 'standard_id', 'section_id']);
        $this->table('teacher_details', ['user_id', 'organization_id']);
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('exams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('time_tables', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('title')->nullable(); $t->timestamps();
        });

        // A student's own
        $this->table('fee_payments', ['student_detail_id', 'standard_id', 'organization_id']);
        $this->table('payment_transactions', ['fee_payment_id', 'student_detail_id', 'user_id']);
        $this->table('student_attendances', ['student_detail_id', 'user_id']);
        $this->table('exam_copies', ['exam_id', 'student_detail_id', 'teacher_detail_id', 'user_id', 'standard_id']);
        $this->table('exam_subject_marks', ['exam_copy_id', 'subject_id']);
        $this->table('seat_assignments', ['student_id']);
        $this->table('announcement_reads', ['announcement_id', 'user_id']);
        $this->table('role_user', ['role_id', 'user_id'], false);
        // A teacher's own
        $this->table('teacher_subjects', ['teacher_detail_id', 'standard_id']);
        $this->table('admin_employees', ['teacher_detail_id', 'organization_id']);
        $this->table('admin_salary_payments', ['admin_employee_id']);
        $this->table('teacher_arrangements', ['original_teacher_id', 'substitute_teacher_id']);
        // Made by a teacher for others
        $this->table('home_works', ['user_id', 'standard_id']);
        $this->table('home_work_completions', ['home_work_id', 'student_detail_id', 'user_id']);
        // A class's set-up
        $this->table('standard_subjects', ['standard_id', 'subject_id']);
        $this->table('fee_structures', ['standard_id']);
        // An exam's
        $this->table('admit_cards', ['exam_id', 'student_detail_id', 'standard_id']);
        $this->table('exam_datesheets', ['exam_id', 'standard_id']);
        $this->table('exam_datesheet_papers', ['exam_datesheet_id', 'subject_id']);
        // An event's
        $this->table('time_table_academics', ['time_table_id', 'teacher_detail_id', 'standard_id']);
        $this->table('time_table_locations', ['time_table_id']);
        // The Super Admin's own
        $this->table('super_admin_fee_payments', ['student_detail_id']);

        // Two of each: 1 is deleted, 2 stays.
        foreach ([1, 2] as $n) {
            DB::table('users')->insert([['id' => 10 + $n, 'name' => "Student $n"], ['id' => 20 + $n, 'name' => "Teacher $n"]]);
            DB::table('standards')->insert(['id' => $n, 'name' => "Class $n"]);
            DB::table('student_details')->insert(['id' => $n, 'user_id' => 10 + $n, 'standard_id' => 2]);
            DB::table('teacher_details')->insert(['id' => $n, 'user_id' => 20 + $n]);
            DB::table('exams')->insert(['id' => $n, 'name' => "Exam $n"]);
            DB::table('time_tables')->insert(['id' => $n, 'title' => "Event $n"]);

            DB::table('fee_payments')->insert(['id' => $n, 'student_detail_id' => $n, 'standard_id' => $n]);
            DB::table('payment_transactions')->insert(['fee_payment_id' => $n]);
            DB::table('student_attendances')->insert(['student_detail_id' => $n, 'user_id' => 10 + $n]);
            DB::table('exam_copies')->insert(['id' => $n, 'exam_id' => $n, 'student_detail_id' => $n, 'teacher_detail_id' => $n, 'user_id' => 20 + $n, 'standard_id' => $n]);
            DB::table('exam_subject_marks')->insert(['exam_copy_id' => $n, 'subject_id' => 5]);
            DB::table('seat_assignments')->insert(['student_id' => $n]);
            DB::table('announcement_reads')->insert(['announcement_id' => 1, 'user_id' => 10 + $n]);
            DB::table('role_user')->insert(['role_id' => 1, 'user_id' => 20 + $n]);

            DB::table('teacher_subjects')->insert(['teacher_detail_id' => $n, 'standard_id' => $n]);
            DB::table('admin_employees')->insert(['id' => $n, 'teacher_detail_id' => $n]);
            DB::table('admin_salary_payments')->insert(['admin_employee_id' => $n]);
            DB::table('teacher_arrangements')->insert(['original_teacher_id' => $n, 'substitute_teacher_id' => 3]);

            DB::table('home_works')->insert(['id' => $n, 'user_id' => 20 + $n, 'standard_id' => $n]);
            DB::table('home_work_completions')->insert(['home_work_id' => $n, 'student_detail_id' => 2, 'user_id' => 12]);
            DB::table('standard_subjects')->insert(['standard_id' => $n, 'subject_id' => 5]);
            DB::table('fee_structures')->insert(['standard_id' => $n]);

            DB::table('admit_cards')->insert(['exam_id' => $n, 'student_detail_id' => $n, 'standard_id' => $n]);
            DB::table('exam_datesheets')->insert(['id' => $n, 'exam_id' => $n, 'standard_id' => $n]);
            DB::table('exam_datesheet_papers')->insert(['exam_datesheet_id' => $n, 'subject_id' => 5]);

            DB::table('time_table_academics')->insert(['time_table_id' => $n, 'teacher_detail_id' => $n, 'standard_id' => $n]);
            DB::table('time_table_locations')->insert(['time_table_id' => $n]);
            DB::table('super_admin_fee_payments')->insert(['student_detail_id' => $n]);
        }
        // Teacher 1 standing in for teacher 3.
        DB::table('teacher_arrangements')->insert(['original_teacher_id' => 3, 'substitute_teacher_id' => 1]);
    }

    private function col(string $table, string $column): array
    {
        return DB::table($table)->orderBy('id')->pluck($column)->all();
    }

    public function test_a_student_takes_their_fees_attendance_marks_and_login_rows(): void
    {
        StudentDetail::find(1)->delete();

        $this->assertSame([2], $this->col('fee_payments', 'student_detail_id'));
        $this->assertSame([2], $this->col('payment_transactions', 'fee_payment_id'));   // the payment's own rows too
        $this->assertSame([2], $this->col('student_attendances', 'student_detail_id'));
        $this->assertSame([2], $this->col('exam_copies', 'student_detail_id'));
        $this->assertSame([2], $this->col('exam_subject_marks', 'exam_copy_id'));       // and the copy's marks
        $this->assertSame([2], $this->col('admit_cards', 'student_detail_id'));
        $this->assertSame([2], $this->col('seat_assignments', 'student_id'));
        $this->assertSame([12], $this->col('announcement_reads', 'user_id'));          // the login's rows

        // The Super Admin's own records, and everyone else's, stay.
        $this->assertSame(2, DB::table('super_admin_fee_payments')->count());
        $this->assertSame(2, DB::table('home_works')->count());
        $this->assertSame([2], $this->col('student_details', 'id'));
        $this->assertSame(4, DB::table('users')->count());   // the login itself is the caller's to delete
    }

    public function test_a_teacher_takes_their_own_rows_and_payroll_not_what_they_entered_for_students(): void
    {
        TeacherDetail::find(1)->delete();

        $this->assertSame([2], $this->col('teacher_subjects', 'teacher_detail_id'));
        $this->assertSame([2], $this->col('admin_employees', 'teacher_detail_id'));
        $this->assertSame([2], $this->col('admin_salary_payments', 'admin_employee_id'));
        // Their arrangements, as the absent teacher or the one standing in.
        $this->assertSame([2], $this->col('teacher_arrangements', 'original_teacher_id'));
        $this->assertSame([22], $this->col('role_user', 'user_id'));

        // The marks and homework they entered, and the events naming them, stay.
        $this->assertSame([1, 2], $this->col('exam_copies', 'teacher_detail_id'));
        $this->assertSame([21, 22], $this->col('home_works', 'user_id'));
        $this->assertSame([1, 2], $this->col('time_table_academics', 'teacher_detail_id'));
    }

    public function test_a_class_takes_its_set_up_and_content_not_the_students_fees_or_results(): void
    {
        Standard::find(1)->delete();

        $this->assertSame([2], $this->col('standard_subjects', 'standard_id'));
        $this->assertSame([2], $this->col('fee_structures', 'standard_id'));
        $this->assertSame([2], $this->col('home_works', 'standard_id'));
        $this->assertSame([2], $this->col('home_work_completions', 'home_work_id'));   // the homework's own rows
        $this->assertSame([2], $this->col('teacher_subjects', 'standard_id'));

        // The students' fees, marks and cards from their time in it stay.
        $this->assertSame([1, 2], $this->col('fee_payments', 'standard_id'));
        $this->assertSame([1, 2], $this->col('exam_copies', 'standard_id'));
        $this->assertSame([1, 2], $this->col('admit_cards', 'standard_id'));
        $this->assertSame(2, DB::table('student_details')->count());
    }

    public function test_an_exam_takes_its_marks_cards_and_datesheets(): void
    {
        Exam::find(1)->delete();

        $this->assertSame([2], $this->col('exam_copies', 'exam_id'));
        $this->assertSame([2], $this->col('exam_subject_marks', 'exam_copy_id'));
        $this->assertSame([2], $this->col('admit_cards', 'exam_id'));
        $this->assertSame([2], $this->col('exam_datesheets', 'exam_id'));
        $this->assertSame([2], $this->col('exam_datesheet_papers', 'exam_datesheet_id'));
        $this->assertSame([1, 2], $this->col('student_details', 'id'));
    }

    public function test_an_event_takes_its_class_and_place(): void
    {
        TimeTable::find(1)->delete();

        $this->assertSame([2], $this->col('time_table_academics', 'time_table_id'));
        $this->assertSame([2], $this->col('time_table_locations', 'time_table_id'));
        $this->assertSame([2], $this->col('time_tables', 'id'));
    }
}
