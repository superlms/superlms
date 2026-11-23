<?php

namespace Tests\Feature;

use App\Models\Biometric\BiometricCommand;
use App\Models\Biometric\BiometricDevice;
use App\Models\Biometric\BiometricPunch;
use App\Models\Biometric\BiometricTemplate;
use App\Services\AppPushNotifier;
use App\Support\Biometric;
use App\Support\BiometricFk;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        (require base_path('database/migrations/2026_11_23_190000_add_fk_protocol_to_biometric_devices.php'))->up();

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Test School']);
        $su = DB::table('users')->insertGetId(['name' => 'Anushka', 'role' => 'user', 'organization_id' => $this->org]);
        $this->student = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $su, 'full_name' => 'ANUSHKA']);
        $tu = DB::table('users')->insertGetId(['name' => 'Ravi Sharma', 'role' => 'teacher', 'organization_id' => $this->org]);
        $this->teacher = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $tu]);

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
        $this->punch('1' . $this->student, '20190921191623')->assertOk();
        $this->punch('1' . $this->student, '20270101090000')->assertOk();
        $this->assertSame(0, BiometricPunch::count());
        $this->assertSame(0, DB::table('student_attendances')->count());
    }

    public function test_a_punch_today_marks_the_student_present(): void
    {
        $r = $this->punch('1' . $this->student, '20261123081200')->assertOk();
        $this->assertSame('OK', $r->headers->get('response_code'));

        $day = DB::table('student_attendances')->where('student_detail_id', $this->student)->first();
        $this->assertSame(1, (int) $day->status);
        $this->assertSame('present', BiometricPunch::first()->result);

        // Sent again (it missed our answer): still one punch.
        $this->punch('1' . $this->student, '20261123081200')->assertOk();
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
        $this->assertSame('1' . $this->student, $json['user_id']);
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
        $this->assertSame(['1' . $this->student, '2' . $this->teacher], $seen);
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
            'user_id' => '1' . $this->student, 'user_name' => 'ANUSHKA', 'user_privilege' => 'USER',
            'enroll_data_array' => [
                ['backup_number' => 0, 'enroll_data' => 'BIN_1'],
                ['backup_number' => 11, 'enroll_data' => 'BIN_2'],
            ],
        ], [$finger, $card]);

        $this->assertSame('OK', $this->fk('realtime_enroll_data', $body)->headers->get('response_code'));
        $this->assertSame(2, BiometricTemplate::where('pin', '1' . $this->student)->count());

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

    public function test_a_long_message_in_blocks_is_put_together(): void
    {
        $finger = random_bytes(2000);
        $body = BiometricFk::frame([
            'user_id' => '1' . $this->student, 'user_name' => 'ANUSHKA', 'user_privilege' => 'USER',
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
