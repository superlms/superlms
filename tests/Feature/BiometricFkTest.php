<?php

namespace Tests\Feature;

use App\Livewire\Admin\Attendance as AttendancePage;
use App\Livewire\Admin\BiometricCard as BiometricCardPanel;
use App\Models\Biometric\BiometricCommand;
use App\Models\Biometric\BiometricDevice;
use App\Models\Biometric\BiometricPunch;
use App\Models\Biometric\BiometricTemplate;
use App\Models\User;
use App\Services\AppPushNotifier;
use App\Support\Biometric;
use App\Support\BiometricFk;
use App\Support\StudentNumbers;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SecurEye-style machines (FK push): the request in these tests is the one
 * a real machine sent to the server, byte for byte.
 */
class BiometricFkTest extends TestCase
{
    private const SERIAL = '201901012812';
    private const CAPTURED = '{"fk_bin_data_lib":"FKDATAHS101","user_id":"1","verify_mode":"34","io_mode":"0","io_time":"20190921191623"}';

    private int $org = 7;
    private int $student;
    private int $teacher;
    private string $sNo = '9001';
    private string $tNo = '';
    private BiometricDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32)), 'cache.default' => 'array']);
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:15:00'));

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('image')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('roll_no')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('employee_id')->nullable(); $t->timestamps();
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
        (require base_path('database/migrations/2026_11_23_190000_add_fk_protocol_to_biometric_devices.php'))->up();
        (require base_path('database/migrations/2026_11_23_213000_fk_roll_numbers_and_teacher_status_times.php'))->up();
        (require base_path('database/migrations/2026_11_23_223000_teacher_machine_numbers_and_student_status_times.php'))->up();

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Test School']);
        $su = DB::table('users')->insertGetId(['name' => 'Anushka', 'role' => 'user', 'organization_id' => $this->org]);
        $this->student = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $su, 'full_name' => 'ANUSHKA', 'roll_no' => '09001']);
        $tu = DB::table('users')->insertGetId(['name' => 'Ravi Sharma', 'role' => 'teacher', 'organization_id' => $this->org]);
        $this->teacher = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $tu]);
        $this->tNo = (string) (BiometricFk::TEACHER_BASE + $this->teacher);

        Biometric::setEnabled($this->org, true);
        Biometric::saveTimes($this->org, ['student_last_time' => '09:00']);
        $this->device = Biometric::addDevice($this->org, self::SERIAL, 'Main');

        $this->app->instance(AppPushNotifier::class, new class extends AppPushNotifier {
            public function __construct() {}
            public function biometricPunch(int $userId, string $title, string $body): void {}
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fk(string $code, string $body = '', array $more = [], string $serial = self::SERIAL)
    {
        $server = ['HTTP_REQUEST_CODE' => $code, 'HTTP_DEV_ID' => $serial, 'HTTP_TRANS_ID' => '0', 'HTTP_BLK_NO' => '0', 'CONTENT_TYPE' => 'application/octet-stream'];
        foreach ($more as $k => $v) {
            $server['HTTP_' . strtoupper($k)] = $v;
        }

        return $this->call('POST', '/fkweb', [], [], [], $server, $body);
    }

    private function punch(string $pin, string $time)
    {
        return $this->fk('realtime_glog', BiometricFk::frame([
            'fk_bin_data_lib' => 'FKDATAHS101', 'user_id' => $pin, 'verify_mode' => '34', 'io_mode' => '0', 'io_time' => $time,
        ]));
    }

    /** Past the clock and the two read-only questions; the next receive_cmd is the first user. */
    private function pastFirstContact(): void
    {
        foreach (['SET_TIME', 'GET_DEVICE_STATUS', 'GET_USER_ID_LIST'] as $expected) {
            $r = $this->fk('receive_cmd');
            $this->assertSame($expected, $r->headers->get('cmd_code'));
            $this->fk('send_cmd_result', '', ['TRANS_ID' => $r->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);
        }
    }

    public function test_the_machines_real_request_is_read_and_answered_as_it_expects(): void
    {
        // The exact length prefix the machine sent: JSON + NUL.
        $raw = pack('V', strlen(self::CAPTURED) + 1) . self::CAPTURED . "\0";
        $this->assertSame(112, strlen($raw));
        $this->assertSame($raw, BiometricFk::frame(json_decode(self::CAPTURED, true)));

        $r = $this->fk('realtime_glog', $raw)->assertOk();
        $this->assertSame('OK', $r->headers->get('response_code'));
        $this->assertSame('0', $r->headers->get('trans_id'));
        $this->assertSame('0', $r->headers->get('Content-Length'));
        $this->assertSame('', $r->getContent());

        $device = $this->device->fresh();
        $this->assertSame('fk', $device->protocol);
        $this->assertNotNull($device->last_seen_at);
    }

    public function test_old_and_future_punches_never_mark_a_day(): void
    {
        // A number that reads as this student, from before the machine came here.
        $this->punch($this->sNo, '20190921191623')->assertOk();
        $this->punch($this->sNo, '20270101090000')->assertOk();
        $this->assertSame(0, BiometricPunch::count());
        $this->assertSame(0, DB::table('student_attendances')->count());
    }

    public function test_a_punch_today_marks_the_student_present(): void
    {
        $r = $this->punch($this->sNo, '20261123081200')->assertOk();
        $this->assertSame('OK', $r->headers->get('response_code'));

        $day = DB::table('student_attendances')->where('student_detail_id', $this->student)->first();
        $this->assertSame(1, (int) $day->status);
        $this->assertSame('present', BiometricPunch::first()->result);

        // Sent again (it missed our answer): still one punch.
        $this->punch($this->sNo, '20261123081200')->assertOk();
        $this->assertSame(1, BiometricPunch::count());
    }

    public function test_a_machine_not_added_keeps_its_punches_and_is_listed(): void
    {
        $r = $this->fk('realtime_glog', pack('V', strlen(self::CAPTURED) + 1) . self::CAPTURED . "\0", [], 'NEWMACHINE01');
        $this->assertSame('ERROR', $r->headers->get('response_code'));
        $this->assertArrayHasKey('NEWMACHINE01', Biometric::unknown());
    }

    public function test_first_contact_sets_the_clock_then_one_test_user_until_released(): void
    {
        $this->fk('realtime_glog', pack('V', strlen(self::CAPTURED) + 1) . self::CAPTURED . "\0");

        $time = $this->fk('receive_cmd');
        $this->assertSame('SET_TIME', $time->headers->get('cmd_code'));
        $this->assertSame(['time' => '20261123081500'], BiometricFk::parse($time->getContent())[0]);
        $this->fk('send_cmd_result', '', ['TRANS_ID' => $time->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);

        foreach (['GET_DEVICE_STATUS', 'GET_USER_ID_LIST'] as $q) {
            $r = $this->fk('receive_cmd');
            $this->assertSame($q, $r->headers->get('cmd_code'));
            $this->fk('send_cmd_result', '', ['TRANS_ID' => $r->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);
        }

        // One user, and only one.
        $first = $this->fk('receive_cmd');
        $this->assertSame('SET_USER_INFO', $first->headers->get('cmd_code'));
        $this->assertSame((string) strlen($first->getContent()), $first->headers->get('Content-Length'));
        [$json] = BiometricFk::parse($first->getContent());
        $this->assertSame($this->sNo, $json['user_id']);
        $this->assertSame('ANUSHKA', $json['user_name']);
        $this->assertSame('USER', $json['user_privilege']);
        $this->assertSame([], $json['enroll_data_array']);

        // Not answered yet: nothing more.
        $this->assertSame('ERROR_NO_CMD', $this->fk('receive_cmd')->headers->get('response_code'));

        $this->fk('send_cmd_result', '', ['TRANS_ID' => $first->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);
        $held = $this->fk('receive_cmd');
        $this->assertSame('ERROR_NO_CMD', $held->headers->get('response_code'));
        $this->assertNull($held->headers->get('cmd_code'));
        $this->assertStringContainsString('Check it on the machine', BiometricFk::status($this->device->fresh()));

        // Checked: Send everyone lets the whole school go.
        Biometric::queueEveryone($this->device->fresh());
        $this->assertTrue($this->device->fresh()->fk_released);
        $seen = [];
        for ($i = 0; $i < 2; $i++) {
            $r = $this->fk('receive_cmd');
            $this->assertSame('SET_USER_INFO', $r->headers->get('cmd_code'));
            $seen[] = BiometricFk::parse($r->getContent())[0]['user_id'];
            $this->fk('send_cmd_result', '', ['TRANS_ID' => $r->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);
        }
        $this->assertSame([$this->sNo, $this->tNo], $seen);
        $this->assertNull(BiometricFk::status($this->device->fresh()));
    }

    public function test_a_refused_test_user_stops_there_and_busy_waits(): void
    {
        $this->fk('realtime_glog', pack('V', strlen(self::CAPTURED) + 1) . self::CAPTURED . "\0");
        $this->pastFirstContact();

        // Someone is in the machine's menu: the same user again after a moment.
        $r = $this->fk('receive_cmd');
        $id = $r->headers->get('trans_id');
        $this->fk('send_cmd_result', '', ['TRANS_ID' => $id, 'CMD_RETURN_CODE' => 'BUSY']);
        $this->assertSame('pending', BiometricCommand::find($id)->status);
        $this->assertSame('ERROR_NO_CMD', $this->fk('receive_cmd')->headers->get('response_code'));

        Carbon::setTestNow(now()->addSeconds(20));
        $again = $this->fk('receive_cmd');
        $this->assertSame($id, $again->headers->get('trans_id'));

        $this->fk('send_cmd_result', '', ['TRANS_ID' => $id, 'CMD_RETURN_CODE' => 'ERROR_INVALID_PARAM']);
        $this->assertSame('failed', BiometricCommand::find($id)->status);
        $this->assertSame('ERROR_NO_CMD', $this->fk('receive_cmd')->headers->get('response_code'));
        $this->assertStringContainsString('refused the test user', BiometricFk::status($this->device->fresh()));
        $this->assertFalse((bool) $this->device->fresh()->fk_released);
    }

    public function test_an_enrolment_is_kept_and_goes_back_with_the_user(): void
    {
        $this->fk('realtime_glog', pack('V', strlen(self::CAPTURED) + 1) . self::CAPTURED . "\0");

        $finger = random_bytes(1404);
        $card   = "\x01\x02\x03\x04";
        $body = BiometricFk::frame([
            'user_id' => $this->sNo, 'user_name' => 'ANUSHKA', 'user_privilege' => 'USER',
            'enroll_data_array' => [
                ['backup_number' => 0, 'enroll_data' => 'BIN_1'],
                ['backup_number' => 11, 'enroll_data' => 'BIN_2'],
            ],
        ], [$finger, $card]);

        $this->assertSame('OK', $this->fk('realtime_enroll_data', $body)->headers->get('response_code'));
        $this->assertSame(2, BiometricTemplate::where('pin', Biometric::pin('student', $this->student))->count());

        $this->pastFirstContact();
        [$json, $bins] = BiometricFk::parse($this->fk('receive_cmd')->getContent());
        $this->assertSame([['backup_number' => 0, 'enroll_data' => 'BIN_1'], ['backup_number' => 11, 'enroll_data' => 'BIN_2']], $json['enroll_data_array']);
        $this->assertSame($finger, $bins[1]);
        $this->assertSame($card, $bins[2]);

        // The log shows a card's bytes, never a fingerprint's.
        $enrolled = collect(BiometricFk::log($this->device->fresh()))->firstWhere('kind', 'enrolled (2 kept)');
        $this->assertSame('1404', $enrolled['bins']['BIN_1']);
        $this->assertSame('4:01020304', $enrolled['bins']['BIN_2']);

        // An ADMS machine of the same school is never given them.
        $adms = Biometric::addDevice($this->org, 'ADMS0001');
        $this->assertSame(0, BiometricCommand::where('biometric_device_id', $adms->id)->where('command', 'like', '%FINGERTMP%')->count());
    }

    /** What the real machine sent when a card was enrolled on its Register screen. */
    private function enrolCard(string $pin, string $digits = '7512310')
    {
        return $this->fk('realtime_enroll_data', BiometricFk::frame([
            'user_id' => $pin, 'user_name' => 'AABHA KU', 'user_privilege' => 'USER',
            'enroll_data_array' => [['backup_number' => 11, 'enroll_data' => 'BIN_1']],
        ], [$digits]));
    }

    private function admin(): void
    {
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    public function test_a_card_enrolled_on_the_machine_becomes_the_persons_and_goes_back_with_them(): void
    {
        $this->assertSame('OK', $this->enrolCard($this->sNo)->headers->get('response_code'));

        $this->assertSame('7512310', Biometric::cardOf('student', $this->student));
        $this->assertSame(0, BiometricTemplate::where('fid', 11)->count());

        $this->pastFirstContact();
        [$json, $bins] = BiometricFk::parse($this->fk('receive_cmd')->getContent());
        $this->assertSame($this->sNo, $json['user_id']);
        $this->assertSame([['backup_number' => 11, 'enroll_data' => 'BIN_1']], $json['enroll_data_array']);
        $this->assertSame('7512310', $bins[1]);
    }

    public function test_the_view_panel_shows_only_the_card_number(): void
    {
        $this->fk('receive_cmd');     // the machine has called: it is an FK machine
        $this->admin();
        DB::table('teacher_details')->where('id', $this->teacher)->update(['employee_id' => '0058']);

        foreach ([['student', $this->student, $this->sNo], ['teacher', $this->teacher, '58']] as [$type, $id, $number]) {
            Livewire::test(BiometricCardPanel::class, ['holderType' => $type, 'holderId' => $id])
                ->assertSee('Card Number')->assertSee($number)
                ->assertDontSee('Assign card')->assertDontSee('Waiting for the card')
                ->assertDontSee('Register')->assertDontSeeHtml('wire:poll');
        }
    }

    public function test_a_card_enrolled_under_the_employee_id_is_the_teachers(): void
    {
        DB::table('teacher_details')->where('id', $this->teacher)->update(['employee_id' => '58']);

        $this->enrolCard('58', '7603283');

        $this->assertSame('7603283', Biometric::cardOf('teacher', $this->teacher));
    }

    public function test_a_card_someone_else_has_is_not_taken(): void
    {
        $this->fk('receive_cmd');
        $this->enrolCard($this->tNo);
        $this->enrolCard($this->sNo);

        $this->assertNull(Biometric::cardOf('student', $this->student));
        $this->assertSame('7512310', Biometric::cardOf('teacher', $this->teacher));
    }

    public function test_the_employee_id_is_the_teachers_machine_number_and_marks_them(): void
    {
        DB::table('teacher_details')->where('id', $this->teacher)->update(['employee_id' => '0058']);
        $device = $this->device->fresh();
        $pin    = Biometric::pin('teacher', $this->teacher);

        $this->assertSame('58', BiometricFk::machineNumber($device, $pin));
        $this->assertSame($pin, BiometricFk::internalPin($device, '58'));
        // The old number still reads as them until the machine has moved.
        $this->assertSame($pin, BiometricFk::internalPin($device, $this->tNo));

        $this->punch('58', '20261123081200')->assertOk();
        $this->assertSame(1, (int) DB::table('teacher_attendances')->where('teacher_detail_id', $this->teacher)->value('status'));
    }

    public function test_an_employee_id_the_machine_cannot_use_keeps_the_teachers_own_number(): void
    {
        $user = fn ($name) => DB::table('users')->insertGetId(['name' => $name, 'role' => 'teacher', 'organization_id' => $this->org]);
        $add  = fn ($name, $employeeId) => DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $user($name), 'employee_id' => $employeeId]);

        $letters = $add('Letters', 'EMP012');
        $roll    = $add('Same As A Roll', '9001');
        $twinA   = $add('Twin A', '77');
        $twinB   = $add('Twin B', '077');
        $numbers = BiometricFk::teacherNumbers($this->org);

        foreach ([$letters, $roll, $twinA, $twinB] as $id) {
            $this->assertSame((string) (BiometricFk::TEACHER_BASE + $id), $numbers[$id]);
        }
        // Roll 9001 stays the student's, and 77 is nobody's.
        $this->assertSame(Biometric::pin('student', $this->student), BiometricFk::internalPin($this->device->fresh(), '9001'));
        $this->assertNull(BiometricFk::internalPin($this->device->fresh(), '77'));
    }

    /** A released machine that has been through first contact, with nothing queued. */
    private function liveMachine(): void
    {
        $this->pastFirstContact();
        BiometricCommand::where('biometric_device_id', $this->device->id)->delete();
        $this->device->refresh()->forceFill(['fk_released' => true, 'fk_numbers' => 'roll'])->save();
    }

    /** Runs the queue as the machine would; each command's code and number. */
    private function drainMachine(): array
    {
        $seen = [];
        while (true) {
            $r = $this->fk('receive_cmd');
            if ($r->headers->get('response_code') === 'ERROR_NO_CMD') {
                return $seen;
            }
            $seen[] = $r->headers->get('cmd_code') . ' ' . BiometricFk::parse($r->getContent())[0]['user_id'];
            $this->fk('send_cmd_result', '', ['TRANS_ID' => $r->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);
        }
    }

    public function test_a_teacher_moves_to_their_employee_id_on_the_machine_with_their_card(): void
    {
        $this->liveMachine();
        $this->enrolCard($this->tNo, '7603283');
        BiometricCommand::where('biometric_device_id', $this->device->id)->delete();
        DB::table('teacher_details')->where('id', $this->teacher)->update(['employee_id' => '58']);

        Carbon::setTestNow(now()->addMinutes(2));
        $first = $this->fk('receive_cmd');
        $this->assertSame(['user_id' => $this->tNo], BiometricFk::parse($first->getContent())[0]);
        $this->fk('send_cmd_result', '', ['TRANS_ID' => $first->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);
        $second = $this->fk('receive_cmd');
        $this->assertSame(['user_id' => '58'], BiometricFk::parse($second->getContent())[0]);
        $this->fk('send_cmd_result', '', ['TRANS_ID' => $second->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);

        $back = $this->fk('receive_cmd');
        $this->assertSame('SET_USER_INFO', $back->headers->get('cmd_code'));
        [$json, $bins] = BiometricFk::parse($back->getContent());
        $this->assertSame('58', $json['user_id']);
        $this->assertSame('7603283', $bins[1]);
        $this->fk('send_cmd_result', '', ['TRANS_ID' => $back->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);

        // Done once: no more work for it.
        Carbon::setTestNow(now()->addMinutes(2));
        $this->assertSame([], $this->drainMachine());
    }

    public function test_two_teachers_swapping_employee_ids_are_both_back_on_the_machine(): void
    {
        $user  = DB::table('users')->insertGetId(['name' => 'Second', 'role' => 'teacher', 'organization_id' => $this->org]);
        $other = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $user, 'employee_id' => '200']);
        DB::table('teacher_details')->where('id', $this->teacher)->update(['employee_id' => '100']);
        $this->liveMachine();
        $this->device->refresh()->forceFill(['fk_teacher_numbers' => [$this->teacher => '100', $other => '200']])->save();

        DB::table('teacher_details')->where('id', $this->teacher)->update(['employee_id' => '200']);
        DB::table('teacher_details')->where('id', $other)->update(['employee_id' => '100']);

        Carbon::setTestNow(now()->addMinutes(2));
        $seen = $this->drainMachine();

        $this->assertSame(['DELETE_USER 100', 'DELETE_USER 200'], array_slice($seen, 0, 2));
        $this->assertEqualsCanonicalizing(['SET_USER_INFO 200', 'SET_USER_INFO 100'], array_slice($seen, 2));
    }

    public function test_a_number_a_student_took_is_never_deleted_from_the_machine(): void
    {
        DB::table('teacher_details')->where('id', $this->teacher)->update(['employee_id' => '500']);
        $this->liveMachine();
        $this->device->refresh()->forceFill(['fk_teacher_numbers' => [$this->teacher => '500']])->save();

        DB::table('student_details')->where('id', $this->student)->update(['roll_no' => '500']);

        Carbon::setTestNow(now()->addMinutes(2));
        $seen = $this->drainMachine();

        $this->assertNotContains('DELETE_USER 500', $seen);
        $this->assertContains('SET_USER_INFO ' . $this->tNo, $seen);
    }

    public function test_removing_a_card_takes_it_off_the_machine_and_keeps_the_finger(): void
    {
        $finger = random_bytes(600);
        $this->fk('realtime_enroll_data', BiometricFk::frame([
            'user_id' => $this->sNo, 'user_name' => 'ANUSHKA', 'user_privilege' => 'USER',
            'enroll_data_array' => [['backup_number' => 0, 'enroll_data' => 'BIN_1'], ['backup_number' => 11, 'enroll_data' => 'BIN_2']],
        ], [$finger, '7512310']));
        $this->pastFirstContact();

        BiometricCommand::where('biometric_device_id', $this->device->id)->delete();
        $this->device->forceFill(['fk_released' => true])->save();

        Biometric::removeCard($this->org, 'student', $this->student);
        $this->assertNull(Biometric::cardOf('student', $this->student));

        $first = $this->fk('receive_cmd');
        $this->assertSame('DELETE_USER', $first->headers->get('cmd_code'));
        $this->assertSame(['user_id' => $this->sNo], BiometricFk::parse($first->getContent())[0]);
        $this->fk('send_cmd_result', '', ['TRANS_ID' => $first->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);

        $second = $this->fk('receive_cmd');
        $this->assertSame('SET_USER_INFO', $second->headers->get('cmd_code'));
        [$json, $bins] = BiometricFk::parse($second->getContent());
        $this->assertSame([['backup_number' => 0, 'enroll_data' => 'BIN_1']], $json['enroll_data_array']);
        $this->assertSame($finger, $bins[1]);
    }

    public function test_a_school_with_only_adms_machines_keeps_the_usb_reader_box(): void
    {
        $this->device->forceFill(['protocol' => null])->save();
        $this->admin();

        Livewire::test(BiometricCardPanel::class, ['holderType' => 'student', 'holderId' => $this->student])
            ->call('listen')
            ->assertSee('Tap the card on the USB reader')
            ->assertSeeHtml('placeholder="Card number"');
    }

    public function test_the_machine_number_is_the_roll_number(): void
    {
        $this->fk('receive_cmd');
        $device = $this->device->fresh();
        $pin    = Biometric::pin('student', $this->student);

        // Roll 09001: the machine keeps numbers, so 9001.
        $this->assertSame('9001', BiometricFk::machineNumber($device, $pin));
        $this->assertSame($pin, BiometricFk::internalPin($device, '9001'));
        $this->assertSame(Biometric::pin('teacher', $this->teacher), BiometricFk::internalPin($device, $this->tNo));

        // The old kind of number is nobody now, and marks nobody.
        $this->assertNull(BiometricFk::internalPin($device, $pin));
        $this->punch($pin, '20261123081200')->assertOk();
        $this->assertSame(0, BiometricPunch::count());
    }

    public function test_a_shared_or_unusable_roll_gets_a_number_of_its_own(): void
    {
        $this->fk('receive_cmd');
        $device = $this->device->fresh();
        $twin   = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'TWIN', 'roll_no' => '9001']);
        $letter = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'LETTER', 'roll_no' => 'A12']);

        $this->assertSame((string) (BiometricFk::STUDENT_BASE + $this->student), BiometricFk::rollNumberOf($this->org, 'student', $this->student));
        $this->assertSame((string) (BiometricFk::STUDENT_BASE + $twin), BiometricFk::rollNumberOf($this->org, 'student', $twin));
        $this->assertSame((string) (BiometricFk::STUDENT_BASE + $letter), BiometricFk::rollNumberOf($this->org, 'student', $letter));
        $this->assertNull(BiometricFk::internalPin($device, '9001'));
        $this->assertSame(Biometric::pin('student', $twin), BiometricFk::internalPin($device, (string) (BiometricFk::STUDENT_BASE + $twin)));
    }

    public function test_a_machine_on_old_numbers_moves_to_roll_numbers_safely(): void
    {
        $this->fk('receive_cmd');
        $this->fk('send_cmd_result', '', ['TRANS_ID' => 'T1', 'CMD_RETURN_CODE' => 'OK']);
        foreach (['GET_DEVICE_STATUS', 'GET_USER_ID_LIST'] as $q) {
            $r = $this->fk('receive_cmd');
            $this->fk('send_cmd_result', '', ['TRANS_ID' => $r->headers->get('trans_id'), 'CMD_RETURN_CODE' => 'OK']);
        }
        BiometricCommand::where('biometric_device_id', $this->device->id)->delete();
        $this->device->refresh()->forceFill(['fk_numbers' => 'id', 'fk_released' => true])->save();

        // On the old numbers, the old number still works.
        $old = Biometric::pin('student', $this->student);
        $this->punch($old, '20261123081000')->assertOk();
        $this->assertSame(1, BiometricPunch::count());

        DB::table('biometric_cards')->insert(['organization_id' => $this->org, 'holder_type' => 'student', 'holder_id' => $this->student, 'card_number' => '7512310']);
        $this->assertSame(2, BiometricFk::renumber($this->device->fresh()));

        // While it moves, nothing counts.
        $this->punch('9001', '20261123081400')->assertOk();
        $this->assertSame(1, BiometricPunch::count());
        $this->assertStringContainsString('Changing to roll numbers', BiometricFk::status($this->device->fresh()));

        // Everyone off first, then everyone back.
        $codes = [];
        $users = [];
        for ($i = 0; $i < 4; $i++) {
            $r = $this->fk('receive_cmd');
            $codes[] = $r->headers->get('cmd_code');
            [$json, $bins] = BiometricFk::parse($r->getContent());
            $users[] = $json['user_id'];
            if ($i === 2) {
                $this->assertSame('7512310', $bins[1]);
            }
            // A user who is not there is no failure.
            $this->fk('send_cmd_result', '', ['TRANS_ID' => $r->headers->get('trans_id'), 'CMD_RETURN_CODE' => $i === 1 ? 'ERROR_NO_USER' : 'OK']);
        }
        $this->assertSame(['DELETE_USER', 'DELETE_USER', 'SET_USER_INFO', 'SET_USER_INFO'], $codes);
        $this->assertSame([$old, Biometric::pin('teacher', $this->teacher), '9001', $this->tNo], $users);

        $this->assertSame('ERROR_NO_CMD', $this->fk('receive_cmd')->headers->get('response_code'));
        $this->assertSame('roll', $this->device->fresh()->fk_numbers);

        Carbon::setTestNow(now()->addMinute());
        $this->punch('9001', now()->format('YmdHis'))->assertOk();
        $this->assertSame(2, BiometricPunch::count());
        $this->assertSame($old, BiometricPunch::orderByDesc('id')->value('pin'));
    }

    public function test_a_new_roll_follows_onto_the_machine(): void
    {
        $this->fk('receive_cmd');
        BiometricCommand::where('biometric_device_id', $this->device->id)->delete();
        DB::table('student_details')->where('id', $this->student)->update(['roll_no' => '10005']);

        StudentNumbers::followOnCards($this->student, '09001', '10005', false);

        $lines = BiometricCommand::where('biometric_device_id', $this->device->id)->orderBy('id')->pluck('command')->all();
        $this->assertSame('FK DELETE_USER 9001', $lines[0]);
        $this->assertSame('FK DELETE_USER 10005', $lines[1]);
        $this->assertStringStartsWith('DATA UPDATE USERINFO PIN=' . Biometric::pin('student', $this->student), $lines[2]);
    }

    public function test_two_students_swapping_rolls_never_delete_each_other(): void
    {
        $this->fk('receive_cmd');
        $other = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'BHAWNA', 'roll_no' => '09002']);
        BiometricCommand::where('biometric_device_id', $this->device->id)->delete();

        DB::table('student_details')->where('id', $this->student)->update(['roll_no' => '09002']);
        StudentNumbers::followOnCards($this->student, '09001', '09002', false);
        DB::table('student_details')->where('id', $other)->update(['roll_no' => '09001']);
        StudentNumbers::followOnCards($other, '09002', '09001', false);

        $lines = BiometricCommand::where('biometric_device_id', $this->device->id)->orderBy('id')->pluck('command')->all();
        $firstSet = array_search(true, array_map(fn ($l) => str_starts_with($l, 'DATA UPDATE'), $lines), true);
        $afterFirstSet = array_slice($lines, $firstSet + 1);
        $this->assertNotContains('FK DELETE_USER 9002', $afterFirstSet);
    }

    private function teacherPunch(string $name, string $time): object
    {
        $user = DB::table('users')->insertGetId(['name' => $name, 'role' => 'teacher', 'organization_id' => $this->org]);
        $id   = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $user]);
        $this->punch((string) (BiometricFk::TEACHER_BASE + $id), '20261123' . $time)->assertOk();

        return (object) ['id' => $id, 'day' => DB::table('teacher_attendances')->where('teacher_detail_id', $id)->first()];
    }

    public function test_teacher_timing_decides_the_status_of_the_first_punch(): void
    {
        Biometric::saveTeacherStatusTimes($this->org, [
            'present'  => ['start' => '07:00', 'end' => '08:30'],
            'half_day' => ['start' => '08:30', 'end' => '11:00'],
            'absent'   => ['start' => '11:00', 'end' => '18:00'],
        ]);

        $this->assertSame(1, (int) $this->teacherPunch('A', '081000')->day->status);
        $this->assertSame(1, (int) $this->teacherPunch('On the minute', '083000')->day->status);
        $this->assertSame(2, (int) $this->teacherPunch('B', '093000')->day->status);
        $this->assertSame(0, (int) $this->teacherPunch('C', '120000')->day->status);

        // Outside every time: nothing is marked, the punch is kept.
        $outside = $this->teacherPunch('D', '200000');
        $this->assertNull($outside->day);
        $this->assertSame('outside', BiometricPunch::where('holder_type', 'teacher')->where('holder_id', $outside->id)->value('result'));
    }

    public function test_a_teachers_day_marked_by_hand_stays_and_no_timing_means_present(): void
    {
        $this->punch($this->tNo, '20261123123000')->assertOk();
        $this->assertSame(1, (int) DB::table('teacher_attendances')->where('teacher_detail_id', $this->teacher)->value('status'));

        Biometric::saveTeacherStatusTimes($this->org, ['absent' => ['start' => '07:00', 'end' => '18:00']]);
        $user = DB::table('users')->insertGetId(['name' => 'By Hand', 'role' => 'teacher', 'organization_id' => $this->org]);
        $id   = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $user]);
        DB::table('teacher_attendances')->insert(['teacher_detail_id' => $id, 'organization_id' => $this->org, 'attendance_date' => '2026-11-23', 'status' => 2]);

        $this->punch((string) (BiometricFk::TEACHER_BASE + $id), '20261123083000')->assertOk();
        $this->assertSame(2, (int) DB::table('teacher_attendances')->where('teacher_detail_id', $id)->value('status'));
    }

    public function test_teacher_timing_refuses_times_that_cannot_work(): void
    {
        $this->assertStringContainsString('both', Biometric::checkStatusTimes(['present' => ['start' => '07:00', 'end' => '']]));
        $this->assertStringContainsString('after', Biometric::checkStatusTimes(['present' => ['start' => '09:00', 'end' => '08:00']]));
        $this->assertStringContainsString('overlap', Biometric::checkStatusTimes([
            'present'  => ['start' => '07:00', 'end' => '09:00'],
            'half_day' => ['start' => '08:30', 'end' => '11:00'],
        ]));
        $this->assertNull(Biometric::checkStatusTimes(Biometric::SUGGESTED_TIMES));
    }

    private function studentPunchAt(string $name, string $roll, string $time): object
    {
        $id = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => $name, 'roll_no' => $roll]);
        $this->punch($roll, '20261123' . $time)->assertOk();

        return (object) ['id' => $id, 'day' => DB::table('student_attendances')->where('student_detail_id', $id)->first()];
    }

    public function test_student_timing_decides_the_status_of_the_first_punch(): void
    {
        Biometric::saveStudentStatusTimes($this->org, Biometric::SUGGESTED_TIMES);

        $this->assertSame(1, (int) $this->studentPunchAt('A', '101', '081000')->day->status);
        $this->assertSame(2, (int) $this->studentPunchAt('B', '102', '093000')->day->status);
        $this->assertSame(0, (int) $this->studentPunchAt('C', '103', '120000')->day->status);

        // Outside every time: nothing is marked, the punch is kept.
        $outside = $this->studentPunchAt('D', '104', '200000');
        $this->assertNull($outside->day);
        $this->assertSame('outside', BiometricPunch::where('holder_type', 'student')->where('holder_id', $outside->id)->value('result'));
    }

    public function test_without_student_timing_the_last_punch_time_still_decides(): void
    {
        $this->assertSame(1, (int) $this->studentPunchAt('On time', '101', '085900')->day->status);
        $this->assertSame(0, (int) $this->studentPunchAt('Late', '102', '093000')->day->status);
    }

    public function test_a_students_day_marked_by_hand_stays_under_student_timing(): void
    {
        Biometric::saveStudentStatusTimes($this->org, ['absent' => ['start' => '07:00', 'end' => '18:00']]);
        DB::table('student_attendances')->insert(['student_detail_id' => $this->student, 'organization_id' => $this->org, 'attendance_date' => '2026-11-23', 'status' => 2]);

        $this->punch($this->sNo, '20261123083000')->assertOk();

        $this->assertSame(2, (int) DB::table('student_attendances')->where('student_detail_id', $this->student)->value('status'));
    }

    private function attendanceTables(): void
    {
        foreach (['standards', 'sections', 'assign_teacher_standards'] as $table) {
            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $t) {
                    $t->id(); $t->string('name')->nullable(); $t->integer('order')->default(0); $t->unsignedBigInteger('organization_id')->nullable();
                    $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
                    $t->unsignedBigInteger('teacher_detail_id')->nullable(); $t->timestamps();
                });
            }
        }
    }

    public function test_the_timing_tab_saves_students_apart_from_teachers(): void
    {
        $this->attendanceTables();
        $this->admin();

        $page = Livewire::test(AttendancePage::class)->call('switchMainTab', 'timing');

        // Saving the teachers never starts the students' suggested times.
        $page->call('saveTiming');
        $this->assertNotSame([], Biometric::teacherStatusTimes($this->org));
        $this->assertSame([], Biometric::studentStatusTimes($this->org));

        $page->set('timingFor', 'student')
            ->assertSee('Student Timing')->assertSee('Not saved yet')
            ->assertSeeHtml('wire:model="studentTiming.present.start"')
            ->set('studentTiming.present.end', '06:00')->call('saveTiming');
        $this->assertSame([], Biometric::studentStatusTimes($this->org));

        $page->set('studentTiming.present.end', '08:45')->set('studentTiming.half_day.start', '08:46')
            ->call('saveTiming')->assertDontSee('Not saved yet');
        $this->assertSame(['start' => '07:00', 'end' => '08:45'], Biometric::studentStatusTimes($this->org)['present']);
        $this->assertSame(['start' => '07:00', 'end' => '08:30'], Biometric::teacherStatusTimes($this->org)['present']);
    }

    public function test_the_teacher_timing_tab_shows_and_saves_the_times(): void
    {
        $this->attendanceTables();
        $this->admin();

        $page = Livewire::test(AttendancePage::class)
            ->call('switchMainTab', 'timing')
            ->assertSee('Teacher Timing')->assertSee('Start time')->assertSee('End time')
            ->assertSee('Half Day')->assertSee('Not saved yet')
            ->assertSet('timing.present.start', '07:00');

        $page->set('timing.present.end', '06:00')->call('saveTiming');
        $this->assertSame([], Biometric::teacherStatusTimes($this->org));

        $page->set('timing.present.end', '08:30')->call('saveTiming')->assertDontSee('Not saved yet');
        $this->assertSame(['start' => '11:01', 'end' => '18:00'], Biometric::teacherStatusTimes($this->org)['absent']);
    }

    public function test_a_long_message_in_blocks_is_put_together(): void
    {
        $finger = random_bytes(2000);
        $body = BiometricFk::frame([
            'user_id' => $this->sNo, 'user_name' => 'ANUSHKA', 'user_privilege' => 'USER',
            'enroll_data_array' => [['backup_number' => 1, 'enroll_data' => 'BIN_1']],
        ], [$finger]);

        $parts = str_split($body, 900);
        foreach ($parts as $i => $part) {
            $no = $i === array_key_last($parts) ? 0 : $i + 1;
            $this->fk('realtime_enroll_data', $part, ['BLK_NO' => (string) $no, 'TRANS_ID' => 'RTEnroll'])->assertOk();
        }

        $kept = BiometricTemplate::where('fid', 1)->first();
        $this->assertSame($finger, base64_decode(substr($kept->template, strlen(BiometricFk::PREFIX))));
    }
}
