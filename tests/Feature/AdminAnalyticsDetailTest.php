<?php

namespace Tests\Feature;

use App\Support\AdminAnalyticsDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Admin → Analytics, each tab's extra detail (2026-11-25): every figure read
 * straight (the page reads each behind a guard, which would hide a mistake).
 */
class AdminAnalyticsDetailTest extends TestCase
{
    private int $org = 5;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-25 11:00:00');

        $t = fn (string $name, callable $cols) => Schema::create($name, function (Blueprint $b) use ($cols) { $b->id(); $cols($b); $b->timestamps(); });
        $t('users', fn ($b) => $b->string('name')->nullable());
        $t('standards', function ($b) { $b->unsignedBigInteger('organization_id'); $b->string('name'); $b->integer('order')->default(0); });
        $t('sections', function ($b) { $b->unsignedBigInteger('standard_id'); $b->string('name'); });
        $t('subjects', function ($b) { $b->unsignedBigInteger('organization_id'); $b->string('name'); });
        $t('student_details', function ($b) {
            $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('standard_id')->nullable(); $b->unsignedBigInteger('section_id')->nullable();
            $b->string('full_name')->nullable(); $b->string('gender')->nullable(); $b->date('date_of_admission')->nullable();
            $b->boolean('transportation_required')->default(false);
        });
        $t('teacher_details', fn ($b) => $b->unsignedBigInteger('organization_id') && $b->unsignedBigInteger('user_id')->nullable());
        $t('student_attendances', function ($b) { $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('student_detail_id')->nullable(); $b->date('attendance_date'); $b->integer('status'); });
        $t('teacher_attendances', function ($b) { $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('teacher_detail_id'); $b->date('attendance_date'); $b->integer('status'); });
        $t('exams', function ($b) { $b->unsignedBigInteger('organization_id'); $b->string('exam_name'); $b->date('start_date')->nullable(); $b->date('end_date')->nullable(); });
        $t('exam_copies', function ($b) {
            $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('exam_id'); $b->unsignedBigInteger('student_detail_id');
            $b->unsignedBigInteger('subject_id')->nullable(); $b->decimal('marks_obtained', 8, 2)->nullable(); $b->decimal('max_marks', 8, 2)->nullable();
            $b->boolean('is_absent')->default(false);
        });
        $t('fee_payments', function ($b) {
            $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('student_detail_id')->nullable(); $b->string('fee_type')->nullable();
            $b->decimal('amount', 10, 2); $b->string('payment_mode')->nullable(); $b->date('payment_date'); $b->string('receipt_number')->nullable();
        });
        $t('transport_fee_payments', function ($b) {
            $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('student_detail_id')->nullable(); $b->decimal('amount', 10, 2);
            $b->string('payment_mode')->nullable(); $b->date('payment_date'); $b->string('receipt_number')->nullable();
        });
        $t('admission_enquiries', function ($b) { $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('standard_id')->nullable(); $b->string('status')->nullable(); $b->string('source')->nullable(); $b->decimal('collected_amount', 10, 2)->nullable(); });
        $t('home_works', function ($b) { $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('standard_id')->nullable(); $b->unsignedBigInteger('subject_id')->nullable(); });
        $t('school_complaints', function ($b) { $b->unsignedBigInteger('organization_id'); $b->unsignedBigInteger('student_detail_id')->nullable(); $b->string('title')->nullable(); });
        $t('reception_visitors', function ($b) { $b->unsignedBigInteger('organization_id'); $b->dateTime('in_at')->nullable(); $b->dateTime('out_at')->nullable(); });
        $t('student_gate_passes', function ($b) { $b->unsignedBigInteger('organization_id'); $b->dateTime('out_at')->nullable(); $b->dateTime('returned_at')->nullable(); });

        DB::table('standards')->insert([['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 5', 'order' => 1], ['id' => 2, 'organization_id' => $this->org, 'name' => 'Class 6', 'order' => 2]]);
        DB::table('sections')->insert([['id' => 1, 'standard_id' => 1, 'name' => 'A'], ['id' => 2, 'standard_id' => 1, 'name' => 'B'], ['id' => 3, 'standard_id' => 2, 'name' => 'A']]);
        DB::table('subjects')->insert([['id' => 1, 'organization_id' => $this->org, 'name' => 'Maths'], ['id' => 2, 'organization_id' => $this->org, 'name' => 'Hindi']]);
        foreach ([[1, 1, 1, 'Aarav', 'Male'], [2, 1, 1, 'Diya', 'female'], [3, 1, 2, 'Kabir', 'M'], [4, 2, 3, 'Meera', 'F']] as [$id, $std, $sec, $name, $g]) {
            DB::table('student_details')->insert(['id' => $id, 'organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec, 'full_name' => $name,
                'gender' => $g, 'date_of_admission' => $id === 4 ? '2025-06-01' : '2026-05-10', 'transportation_required' => $id === 1, 'created_at' => $id === 4 ? '2026-11-20' : '2026-05-10']);
        }
        DB::table('users')->insert(['id' => 9, 'name' => 'Mr. Verma']);
        DB::table('teacher_details')->insert(['id' => 1, 'organization_id' => $this->org, 'user_id' => 9]);
    }

    private function read(string $method, ...$args)
    {
        $m = new \ReflectionMethod(AdminAnalyticsDetail::class, $method);

        return $m->invoke(null, ...$args);
    }

    private function classes(): array
    {
        return [1 => 'Class 5', 2 => 'Class 6'];
    }

    public function test_key_metrics(): void
    {
        $g = $this->read('glance', $this->org, $this->classes());
        $this->assertSame(['students' => 4, 'boys' => 2, 'girls' => 2, 'teachers' => 1, 'classes' => 2, 'sections' => 3, 'subjects' => 2, 'riders' => 1], $g);

        $rows = $this->read('studentsByClass', $this->org, $this->classes());
        $this->assertSame(['name' => 'Class 5', 'sections' => 2, 'students' => 3, 'boys' => 2, 'girls' => 1, 'new' => 0], $rows[0]);
        $this->assertSame(1, $rows[1]['new']);

        DB::table('fee_payments')->insert([
            ['organization_id' => $this->org, 'student_detail_id' => 1, 'fee_type' => 'academic', 'amount' => 1000, 'payment_mode' => 'Cash', 'payment_date' => '2026-11-20', 'receipt_number' => 'R1'],
            ['organization_id' => $this->org, 'student_detail_id' => 2, 'fee_type' => 'academic', 'amount' => 500, 'payment_mode' => 'online', 'payment_date' => '2026-10-15', 'receipt_number' => 'R2'],
        ]);
        $months = collect($this->read('monthAgainstMonth', $this->org))->keyBy(0);
        $this->assertSame([1000.0, 500.0], [(float) $months['Fee collected'][1], (float) $months['Fee collected'][2]]);
        $this->assertSame([1, 0], [$months['New students'][1], $months['New students'][2]]);
    }

    public function test_attendance(): void
    {
        DB::table('student_attendances')->insert([
            ['organization_id' => $this->org, 'student_detail_id' => 1, 'attendance_date' => '2026-11-25', 'status' => 1],
            ['organization_id' => $this->org, 'student_detail_id' => 2, 'attendance_date' => '2026-11-25', 'status' => 0],
            ['organization_id' => $this->org, 'student_detail_id' => 3, 'attendance_date' => '2026-11-25', 'status' => 2],
            ['organization_id' => $this->org, 'student_detail_id' => 2, 'attendance_date' => '2026-11-24', 'status' => 0],
            ['organization_id' => $this->org, 'student_detail_id' => 2, 'attendance_date' => '2026-11-23', 'status' => 1],
        ]);
        DB::table('teacher_attendances')->insert([
            ['organization_id' => $this->org, 'teacher_detail_id' => 1, 'attendance_date' => '2026-11-24', 'status' => 0],
            ['organization_id' => $this->org, 'teacher_detail_id' => 1, 'attendance_date' => '2026-11-25', 'status' => 1],
        ]);

        $today = $this->read('todayByClass', $this->org, $this->classes());
        $this->assertSame(['name' => 'Class 5', 'students' => 3, 'present' => 1, 'half' => 1, 'absent' => 1, 'holiday' => 0, 'unmarked' => 0, 'pct' => 50.0], $today[0]);
        $this->assertSame(1, $today[1]['unmarked']);
        $this->assertNull($today[1]['pct']);

        $abs = $this->read('mostAbsentStudents', $this->org);
        $this->assertSame(['name' => 'Diya', 'class' => 'Class 5 · A', 'absent' => 2, 'present' => 1, 'pct' => 33.0], $abs[0]);
        $this->assertSame('Mr. Verma', $this->read('mostAbsentTeachers', $this->org)[0]['name']);

        $week = collect($this->read('weekdayPattern', $this->org))->keyBy('day');
        $this->assertSame(50.0, $week['Wednesday']['pct']);   // 25 Nov 2026
        $this->assertSame(0.0, $week['Tuesday']['pct']);
    }

    public function test_performance(): void
    {
        DB::table('exams')->insert([['id' => 1, 'organization_id' => $this->org, 'exam_name' => 'Unit Test', 'start_date' => '2026-08-01', 'end_date' => '2026-08-05'],
                                    ['id' => 2, 'organization_id' => $this->org, 'exam_name' => 'Half Yearly', 'start_date' => '2026-10-01', 'end_date' => '2026-10-10']]);
        DB::table('exam_copies')->insert([
            ['organization_id' => $this->org, 'exam_id' => 2, 'student_detail_id' => 1, 'subject_id' => 1, 'marks_obtained' => 90, 'max_marks' => 100, 'is_absent' => 0],
            ['organization_id' => $this->org, 'exam_id' => 2, 'student_detail_id' => 1, 'subject_id' => 2, 'marks_obtained' => 70, 'max_marks' => 100, 'is_absent' => 0],
            ['organization_id' => $this->org, 'exam_id' => 2, 'student_detail_id' => 4, 'subject_id' => 1, 'marks_obtained' => 20, 'max_marks' => 100, 'is_absent' => 0],
            ['organization_id' => $this->org, 'exam_id' => 2, 'student_detail_id' => 4, 'subject_id' => 2, 'marks_obtained' => null, 'max_marks' => 100, 'is_absent' => 1],
            ['organization_id' => $this->org, 'exam_id' => 1, 'student_detail_id' => 1, 'subject_id' => 1, 'marks_obtained' => 40, 'max_marks' => 50, 'is_absent' => 0],
        ]);

        $ex = $this->read('examResults', $this->org, '', '');
        $this->assertSame('Half Yearly', $ex['rows'][0]['exam']);   // latest first
        $this->assertSame(['students' => 2, 'avg' => 45.0, 'passPct' => 50.0, 'high' => 80.0, 'low' => 10.0, 'missed' => 1],
            array_intersect_key($ex['rows'][0], array_flip(['students', 'avg', 'passPct', 'high', 'low', 'missed'])));
        $this->assertCount(1, $this->read('examResults', $this->org, '2', '')['rows']);   // Class 6 only sat the Half Yearly

        $subjects = collect($this->read('subjectAverages', $this->org, '', ''))->keyBy('subject');
        $this->assertSame(['subject' => 'Maths', 'papers' => 3, 'missed' => 0, 'avg' => 60.0, 'high' => 90.0, 'low' => 20.0], $subjects['Maths']);
        $this->assertSame(1, $subjects['Hindi']['missed']);

        $bands = collect($this->read('scoreBands', $this->org, '', ''))->keyBy('label');
        $this->assertSame(1, $bands['75 – 90%']['n']);   // Aarav: 200 / 250
        $this->assertSame(1, $bands['Below 33%']['n']);  // Meera: 20 / 200

        $avg = collect($this->read('classAverages', $this->org, $this->classes()))->keyBy('name');
        $this->assertSame(80.0, $avg['Class 5']['avg']);
        $this->assertSame(10.0, $avg['Class 6']['avg']);
    }

    public function test_admissions_fee_and_operations(): void
    {
        $adm = $this->read('admissionsByClass', $this->org, $this->classes(), 2026);
        $this->assertSame([['name' => 'Class 5', 'n' => 3, 'boys' => 2, 'girls' => 1]], $adm);

        DB::table('admission_enquiries')->insert([
            ['organization_id' => $this->org, 'standard_id' => 1, 'status' => 'pending', 'source' => 'website', 'collected_amount' => null, 'created_at' => '2026-11-10'],
            ['organization_id' => $this->org, 'standard_id' => 1, 'status' => 'admitted', 'source' => null, 'collected_amount' => 1500, 'created_at' => '2026-10-10'],
        ]);
        $apps = $this->read('applications', $this->org, $this->classes());
        $this->assertSame([2, 1, 1, 1500.0], [$apps['total'], $apps['thisMonth'], $apps['website'], $apps['collected']]);
        $this->assertSame([['name' => 'Class 5', 'n' => 2]], $apps['classes']);
        $this->assertSame(1, $this->read('enquiriesByMonth', $this->org)[0]['admission']);   // Nov 2026 first

        DB::table('fee_payments')->insert([
            ['organization_id' => $this->org, 'student_detail_id' => 1, 'fee_type' => 'academic', 'amount' => 1000, 'payment_mode' => 'Cash', 'payment_date' => '2026-11-25', 'receipt_number' => 'R1'],
            ['organization_id' => $this->org, 'student_detail_id' => 2, 'fee_type' => 'academic', 'amount' => 500, 'payment_mode' => 'cash', 'payment_date' => '2026-10-15', 'receipt_number' => 'R2'],
        ]);
        DB::table('transport_fee_payments')->insert(['organization_id' => $this->org, 'student_detail_id' => 1, 'amount' => 300, 'payment_mode' => 'UPI', 'payment_date' => '2026-11-24', 'receipt_number' => 'T1']);

        $w = collect($this->read('feeWindows', $this->org))->mapWithKeys(fn ($r) => [$r[0] => (float) $r[1]]);
        $this->assertSame([1000.0, 300.0, 1300.0, 1300.0, 500.0, 1800.0], [$w['Today'], $w['Yesterday'], $w['This week'], $w['This month'], $w['Last month'], $w['This session']]);

        $modes = collect($this->read('paymentModes', $this->org))->keyBy('mode');
        $this->assertSame(2, $modes['Cash']['n']);
        $this->assertSame(1500.0, (float) $modes['Cash']['amount']);

        $recent = $this->read('recentPayments', $this->org);
        $this->assertSame(['R1', 'T1', 'R2'], array_column($recent, 'receipt'));
        $this->assertSame(['Academic', 'Transport'], [$recent[0]['kind'], $recent[1]['kind']]);

        DB::table('home_works')->insert([['organization_id' => $this->org, 'standard_id' => 1, 'subject_id' => 1, 'created_at' => '2026-11-25 09:00'],
                                         ['organization_id' => $this->org, 'standard_id' => 1, 'subject_id' => 2, 'created_at' => '2026-11-24 09:00']]);
        DB::table('reception_visitors')->insert(['organization_id' => $this->org, 'in_at' => '2026-11-25 09:30', 'out_at' => null]);
        DB::table('student_gate_passes')->insert(['organization_id' => $this->org, 'out_at' => '2026-11-25 10:00', 'returned_at' => '2026-11-25 10:40']);
        DB::table('school_complaints')->insert(['organization_id' => $this->org, 'student_detail_id' => 3, 'title' => 'Late again', 'created_at' => '2026-11-22']);

        $desk = collect($this->read('frontDesk', $this->org))->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);
        $this->assertSame([1, 1, 1, 0, 1, 1, 2], [$desk['Visitors today'], $desk['Visitors still in'], $desk['Gate passes today'], $desk['Not back yet'],
            $desk['Complaints this month'], $desk['Homework today'], $desk['Homework this week']]);
        $hw = $this->read('homeworkByClass', $this->org, $this->classes());
        $this->assertSame(['name' => 'Class 5', 'n' => 2, 'subjects' => 2, 'last' => '25 Nov'], $hw[0]);
        $this->assertSame(['title' => 'Late again', 'name' => 'Kabir', 'class' => 'Class 5 · B', 'date' => '22 Nov 2026'], $this->read('latestComplaints', $this->org)[0]);
    }
}
