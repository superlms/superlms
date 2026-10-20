<?php

namespace Tests\Feature;

use App\Livewire\Admin\BiometricCard;
use App\Livewire\Admin\Setting;
use App\Models\Biometric\BiometricCommand;
use App\Models\Biometric\BiometricDevice;
use App\Models\User;
use App\Services\AppPushNotifier;
use App\Support\Biometric;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Biometric attendance: the machines push punches (ADMS / iclock) and each
 * marks the day — a student by the school's last time Present, later Absent;
 * a teacher's first punch the punch-in, the last the punch-out — and the
 * person hears it on the app. Cards are assigned from a USB reader and sent
 * to the machines with everyone.
 */
class BiometricAttendanceTest extends TestCase
{
    private int $org = 7;
    private int $student;
    private int $studentUser;
    private int $teacher;
    private int $teacherUser;
    private BiometricDevice $device;
    private array $told = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);
        Carbon::setTestNow(Carbon::parse('2026-10-21 10:00:00'));

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('image')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('full_name')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('user_id')->default(0);
            $t->unsignedBigInteger('organization_id'); $t->date('attendance_date')->nullable();
            $t->unsignedTinyInteger('status')->default(0); $t->string('remarks')->nullable();
            $t->unsignedBigInteger('marked_by')->default(0); $t->timestamps();
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id'); $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date')->nullable(); $t->unsignedTinyInteger('status')->default(0);
            $t->string('remarks')->nullable(); $t->unsignedBigInteger('marked_by')->default(0); $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_21_000000_create_biometric_tables.php'))->up();

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Delhi Public School']);
        $this->studentUser = DB::table('users')->insertGetId(['name' => 'Anushka', 'role' => 'user', 'organization_id' => $this->org]);
        $this->student     = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $this->studentUser, 'full_name' => 'ANUSHKA']);
        $this->teacherUser = DB::table('users')->insertGetId(['name' => 'Ravi Sharma', 'role' => 'teacher', 'organization_id' => $this->org]);
        $this->teacher     = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $this->teacherUser]);

        Biometric::setEnabled($this->org, true);
        Biometric::saveTimes($this->org, ['student_last_time' => '08:30', 'teacher_in_time' => '08:00', 'teacher_out_time' => '14:00']);
        $this->device = Biometric::addDevice($this->org, 'rtx20plus001', 'Main gate');

        // What the app would be told.
        $this->told = [];
        $test = $this;
        $this->app->instance(AppPushNotifier::class, new class($test) extends AppPushNotifier {
            public function __construct(private $test) {}
            public function biometricPunch(int $userId, string $title, string $body): void { $this->test->heard($userId, $body); }
        });

        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function heard(int $userId, string $body): void
    {
        $this->told[] = [$userId, $body];
    }

    private function punch(string $pin, string $time, int $verify = 4): void
    {
        $this->call('POST', '/iclock/cdata?SN=RTX20PLUS001&table=ATTLOG&Stamp=9999', [], [], [], ['CONTENT_TYPE' => 'text/plain'],
            "{$pin}\t2026-10-21 {$time}\t0\t{$verify}\t0\t0\n")->assertOk()->assertSee('OK: 1');
    }

    private function studentDay()
    {
        return DB::table('student_attendances')->where('student_detail_id', $this->student)->first();
    }

    public function test_a_machine_is_answered_and_gets_everyone_with_their_card(): void
    {
        // A machine nobody added gets nothing, and is listed for the super admin.
        $this->get('/iclock/cdata?SN=UNKNOWN99&options=all')->assertOk()->assertSee('OK');
        $this->assertArrayHasKey('UNKNOWN99', Biometric::unknown());

        $this->get('/iclock/cdata?SN=RTX20PLUS001&options=all&pushver=2.4.1')->assertOk()
            ->assertSee('GET OPTION FROM: RTX20PLUS001')->assertSee('Realtime=1');
        $this->assertNotNull($this->device->fresh()->last_seen_at);

        // The card first, then the machine asks for its work.
        Biometric::assignCard($this->org, 'student', $this->student, '0012345678');
        $work = $this->get('/iclock/getrequest?SN=RTX20PLUS001')->assertOk()->getContent();
        $this->assertStringContainsString("DATA UPDATE USERINFO PIN=1{$this->student}\tName=ANUSHKA", $work);
        $this->assertStringContainsString("PIN=2{$this->teacher}\tName=Ravi Sharma", $work);
        $this->assertStringContainsString('Card=12345678', $work);

        // It says how they went.
        preg_match('/^C:(\d+):/m', $work, $m);
        $this->call('POST', '/iclock/devicecmd?SN=RTX20PLUS001', [], [], [], [], "ID={$m[1]}&Return=0&CMD=DATA\n")->assertOk();
        $this->assertSame('done', BiometricCommand::find($m[1])->status);
    }

    public function test_a_student_by_the_last_time_is_present_and_later_absent(): void
    {
        $this->punch('1' . $this->student, '07:52:10');
        $day = $this->studentDay();
        $this->assertSame(1, (int) $day->status);
        $this->assertSame('07:52:10', $day->punch_time);
        $this->assertSame('biometric', $day->source);
        $this->assertSame([[$this->studentUser, 'ANUSHKA reached school at 07:52 — marked Present.']], $this->told);

        // The same punch sent again, and a second punch, change nothing.
        $this->punch('1' . $this->student, '07:52:10');
        $this->punch('1' . $this->student, '09:40:00');
        $this->assertSame(1, (int) $this->studentDay()->status);
        $this->assertCount(1, $this->told);
    }

    public function test_a_late_punch_is_absent_until_an_earlier_one_comes_in(): void
    {
        $this->punch('1' . $this->student, '08:45:00');
        $day = $this->studentDay();
        $this->assertSame(0, (int) $day->status);
        $this->assertSame('Late punch 08:45 — after 08:30', $day->remarks);
        $this->assertStringContainsString('marked Absent', $this->told[0][1]);

        // A machine that was offline sends its earlier punch: the first one counts.
        $this->punch('1' . $this->student, '08:20:00');
        $this->assertSame(1, (int) $this->studentDay()->status);
        $this->assertSame('08:20:00', $this->studentDay()->punch_time);
    }

    public function test_a_day_marked_by_hand_present_or_holiday_is_kept(): void
    {
        DB::table('student_attendances')->insert(['student_detail_id' => $this->student, 'user_id' => $this->studentUser,
            'organization_id' => $this->org, 'attendance_date' => '2026-10-21', 'status' => 3, 'remarks' => 'Diwali']);
        $this->punch('1' . $this->student, '08:45:00');
        $this->assertSame(3, (int) $this->studentDay()->status);
        $this->assertSame([], $this->told);
    }

    public function test_a_teacher_punches_in_and_out(): void
    {
        $this->punch('2' . $this->teacher, '08:12:00', 1);      // fingerprint, after 08:00
        $day = DB::table('teacher_attendances')->where('teacher_detail_id', $this->teacher)->first();
        $this->assertSame(1, (int) $day->status);
        $this->assertSame('08:12:00', $day->punch_in);
        $this->assertNull($day->punch_out);
        $this->assertSame('In 08:12 (late)', $day->remarks);

        $this->punch('2' . $this->teacher, '08:13:00', 1);      // the same punch, a minute on
        $this->assertNull(DB::table('teacher_attendances')->where('teacher_detail_id', $this->teacher)->value('punch_out'));

        $this->punch('2' . $this->teacher, '13:30:00', 4);      // card, before 14:00
        $day = DB::table('teacher_attendances')->where('teacher_detail_id', $this->teacher)->first();
        $this->assertSame('13:30:00', $day->punch_out);
        $this->assertSame('In 08:12 (late) · Out 13:30 (early)', $day->remarks);
        $this->assertSame([
            [$this->teacherUser, 'Punched in at 08:12 — marked Present.'],
            [$this->teacherUser, 'Punched out at 13:30.'],
        ], $this->told);
    }

    public function test_nothing_is_marked_while_it_is_off_or_for_someone_unknown(): void
    {
        $this->punch('99999', '07:50:00');
        Biometric::setEnabled($this->org, false);
        $this->punch('1' . $this->student, '07:50:00');

        $this->assertNull($this->studentDay());
        $this->assertSame(['unknown', 'off'], DB::table('biometric_punches')->orderBy('id')->pluck('result')->all());
    }

    public function test_a_card_tapped_on_the_reader_is_assigned_once(): void
    {
        $other = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'BHAWNA']);

        Livewire::test(BiometricCard::class, ['holderType' => 'student', 'holderId' => $this->student])
            ->assertSee('Assign card')
            ->call('listen')->assertSee('Tap the card on the USB reader')
            ->set('cardInput', '0012345678')->call('save')
            ->assertHasNoErrors()->assertSee('12345678');
        $this->assertSame('12345678', Biometric::cardOf('student', $this->student));
        $this->assertTrue(BiometricCommand::where('command', 'like', "%PIN=1{$this->student}\t%Card=12345678%")->exists());

        // The same card for another student: said whose it is.
        Livewire::test(BiometricCard::class, ['holderType' => 'student', 'holderId' => $other])
            ->call('listen')->set('cardInput', '12345678')->call('save')
            ->assertHasErrors('cardInput')->assertSee('already with ANUSHKA');
        $this->assertNull(Biometric::cardOf('student', $other));
    }

    public function test_the_school_sets_its_times(): void
    {
        Livewire::test(Setting::class)
            ->assertSee('Students — last punch time')
            ->set('studentLastTime', '09:15')->set('teacherInTime', '08:30')->set('teacherOutTime', '08:00')
            ->call('saveBiometric')->assertHasErrors('teacherOutTime')
            ->set('teacherOutTime', '15:00')->call('saveBiometric')->assertHasNoErrors();

        $this->assertSame(['student_last_time' => '09:15', 'teacher_in_time' => '08:30', 'teacher_out_time' => '15:00'], Biometric::times($this->org));
    }
}
