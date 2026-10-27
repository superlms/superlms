<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SchoolSms;
use App\Services\SmsService;
use App\Services\WelcomeSms;
use App\Services\WelcomeWhatsApp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The welcome by SMS (Bhash SMS, 2026-10-27): a school the super admin turns
 * SMS on for gets the student's and teacher's login by SMS only — no welcome
 * email, no WhatsApp; every other school keeps both, and so does that school
 * while Bhash is not set up.
 */
class WelcomeSmsTest extends TestCase
{
    private const STUDENT_TEXT = 'Dear {#var#}, welcome to {#var#}. Class: {#var#}, Section: {#var#}. Login - Admission No: {#var#}, Password: {#var#}. Download the app: https://superlms.in/app - SuperLMS';
    private const TEACHER_TEXT = 'Dear {#var#}, welcome to {#var#}. Login - Username: {#var#}, Password: {#var#}. Download the app: https://superlms.in/app - SuperLMS';

    /** What Bhash answers: its body, or a throwable to throw (null: "S.5550123"). */
    private string|\Throwable|null $bhash = null;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('m', 32)),
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '1294658720402506',
            'services.bhash_sms.enabled' => true,
            'services.bhash_sms.url' => 'https://bhashsms.com/api/sendmsg.php',
            'services.bhash_sms.user' => 'superlms',
            'services.bhash_sms.pass' => 'bhash-secret',
            'services.bhash_sms.sender' => 'SPRLMS',
            'services.bhash_sms.entity_id' => '1101234567890123456',
            'services.bhash_sms.entity_param' => 'entityid',
            'services.bhash_sms.template_param' => 'templateid',
            'services.bhash_sms.templates.student_welcome' => ['id' => '1107000000000000001', 'text' => self::STUDENT_TEXT],
            'services.bhash_sms.templates.teacher_welcome' => ['id' => '1107000000000000002', 'text' => self::TEACHER_TEXT],
            'services.zeptomail.api_token' => 'zepto-token',
            'services.zeptomail.student_password_template_key' => 'student-key',
            'services.zeptomail.teacher_password_template_key' => 'teacher-key',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
            'bhashsms.com/*'       => function () {
                if ($this->bhash instanceof \Throwable) {
                    throw $this->bhash;
                }
                return Http::response($this->bhash ?? 'S.5550123');
            },
            'api.zeptomail.in/*'   => Http::response(['data' => []]),
        ]);

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'email', 'username', 'password', 'role', 'mobile_number'] as $c) {
                $t->string($c)->nullable();
            }
            $t->text('password_plain')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('logo')->nullable(); $t->timestamps(); });
        Schema::create('standards', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('sections', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->string('phone')->nullable();
            $t->timestamps();
        });
        Schema::create('account_setup_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->char('token_hash', 64)->unique();
            $t->string('password_hash')->nullable();
            $t->timestamp('expires_at');
            $t->timestamp('opened_at')->nullable();
            $t->timestamps();
        });

        DB::table('organizations')->insert([
            ['id' => 3, 'name' => 'Alpha Public School'],
            ['id' => 4, 'name' => 'Beta School'],
        ]);
        DB::table('standards')->insert(['id' => 1, 'name' => 'Class 5']);
        DB::table('sections')->insert(['id' => 1, 'name' => 'A']);
        DB::table('users')->insert([
            ['id' => 10, 'name' => 'Asha Verma', 'email' => 'asha@example.com', 'role' => 'user', 'organization_id' => 3, 'username' => null, 'mobile_number' => '98765 43210', 'password' => 'x', 'password_plain' => Crypt::encryptString('Xk9#pQ2m!a')],
            ['id' => 20, 'name' => 'Ravi Kumar', 'email' => 'ravi@example.com', 'role' => 'teacher', 'organization_id' => 3, 'username' => 'T-RAVI', 'mobile_number' => '9123456780', 'password' => 'x', 'password_plain' => Crypt::encryptString('Tq7$wE3r')],
            ['id' => 30, 'name' => 'Other Kid', 'email' => 'o@example.com', 'role' => 'user', 'organization_id' => 4, 'username' => null, 'mobile_number' => '9000000001', 'password' => 'x', 'password_plain' => null],
        ]);
        DB::table('student_details')->insert([
            ['user_id' => 10, 'organization_id' => 3, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Asha Verma', 'admission_no' => '26APS100001', 'phone' => null],
            ['user_id' => 30, 'organization_id' => 4, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Other Kid', 'admission_no' => '26BS100001', 'phone' => null],
        ]);

        (require base_path('database/migrations/2026_10_14_200000_create_whatsapp_messages_tables.php'))->up();
        (require base_path('database/migrations/2026_10_27_120000_create_sms_tables.php'))->up();
        DB::table('whatsapp_school_features')->insert([
            ['organization_id' => 3, 'feature' => 'credentials', 'enabled' => true],
            ['organization_id' => 4, 'feature' => 'credentials', 'enabled' => true],
        ]);

        // SMS on for Alpha only.
        SchoolSms::setEnabled(3, 'welcome', true);
    }

    /** @return array<int, array> the query of each request to Bhash, after the response */
    private function smsSent(): array
    {
        $this->app->terminate();   // the SMS goes after the response

        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'bhashsms.com'))
            ->map(function ($pair) {
                parse_str((string) parse_url($pair[0]->url(), PHP_URL_QUERY), $q);
                return $q;
            })
            ->values()->all();
    }

    private function whatsAppSent(): int
    {
        return collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'graph.facebook.com'))->count();
    }

    public function test_the_text_is_the_template_with_its_values_in_order(): void
    {
        $this->assertSame(
            'Dear Asha Verma, welcome to Alpha Public School. Class: Class 5, Section: A. Login - Admission No: 26APS100001, Password: Xk9#pQ2m!a. Download the app: https://superlms.in/app - SuperLMS',
            SmsService::fill(self::STUDENT_TEXT, ['Asha Verma', 'Alpha Public School', 'Class 5', 'A', '26APS100001', 'Xk9#pQ2m!a'])
        );

        // Plain letters, at most 30 to a value, "-" for nothing.
        $text = SmsService::fill('{#var#}|{#var#}|{#var#}|{#var#}', ['Ánanyá  Shàrma', 'St. Xavier’s Senior Secondary School, Jaipur', '', null]);
        [$name, $school, $empty, $missing] = explode('|', $text);
        $this->assertSame('Ananya Sharma', $name);
        $this->assertSame(30, strlen($school));
        $this->assertSame('-', $empty);
        $this->assertSame('-', $missing);

        $this->assertSame(1, SmsService::parts(str_repeat('a', 160)));
        $this->assertSame(2, SmsService::parts(str_repeat('a', 161)));
        $this->assertSame('9876543210', SmsService::normalizePhone('+91 98765-43210'));
        $this->assertNull(SmsService::normalizePhone('12345'));
    }

    public function test_a_student_of_a_school_on_sms_gets_the_welcome_by_sms_only(): void
    {
        WelcomeWhatsApp::student(10);
        $sent = $this->smsSent();

        $this->assertCount(1, $sent);
        $this->assertSame([
            'user'       => 'superlms',
            'pass'       => 'bhash-secret',
            'sender'     => 'SPRLMS',
            'phone'      => '9876543210',
            'text'       => 'Dear Asha Verma, welcome to Alpha Public School. Class: Class 5, Section: A. Login - Admission No: 26APS100001, Password: Xk9#pQ2m!a. Download the app: https://superlms.in/app - SuperLMS',
            'priority'   => 'ndnd',
            'stype'      => 'normal',
            'entityid'   => '1101234567890123456',
            'templateid' => '1107000000000000001',
        ], $sent[0]);
        $this->assertSame(0, $this->whatsAppSent());

        $row = DB::table('sms_messages')->first();
        $this->assertSame([3, 'welcome', 'student_welcome', 10, '9876543210', 'S.5550123', 'sent', 2],
            [(int) $row->organization_id, $row->feature, $row->template, (int) $row->user_id, $row->to_phone, $row->provider_message_id, $row->status, (int) $row->parts]);
        // The text (with the password) is not kept anywhere.
        $this->assertStringNotContainsString('Xk9#pQ2m!a', json_encode(DB::table('sms_messages')->get()));
    }

    public function test_a_teacher_of_a_school_on_sms_gets_the_welcome_by_sms_only(): void
    {
        WelcomeWhatsApp::teacher(20);
        $sent = $this->smsSent();

        $this->assertCount(1, $sent);
        $this->assertSame('9123456780', $sent[0]['phone']);
        $this->assertSame('1107000000000000002', $sent[0]['templateid']);
        $this->assertSame(
            'Dear Ravi Kumar, welcome to Alpha Public School. Login - Username: T-RAVI, Password: Tq7$wE3r. Download the app: https://superlms.in/app - SuperLMS',
            $sent[0]['text']
        );
        $this->assertSame(0, $this->whatsAppSent());
    }

    public function test_a_school_without_sms_keeps_the_whatsapp(): void
    {
        WelcomeWhatsApp::student(30);

        $this->assertSame([], $this->smsSent());
        $this->assertSame(1, $this->whatsAppSent());
        $this->assertFalse(WelcomeSms::on(4));
    }

    public function test_while_bhash_is_not_set_up_the_school_keeps_the_whatsapp(): void
    {
        config(['services.bhash_sms.pass' => null]);

        WelcomeWhatsApp::student(10);

        $this->assertSame([], $this->smsSent());
        $this->assertSame(1, $this->whatsAppSent());
        $this->assertFalse(WelcomeSms::on(3));
    }

    public function test_turned_off_the_school_is_back_on_email_and_whatsapp(): void
    {
        SchoolSms::setEnabled(3, 'welcome', false);

        WelcomeWhatsApp::teacher(20);

        $this->assertSame([], $this->smsSent());
        $this->assertSame(1, $this->whatsAppSent());
    }

    public function test_send_credentials_goes_by_sms_for_a_school_on_sms(): void
    {
        $this->assertSame(
            ['ok' => true, 'message' => 'Credentials sent by SMS to +919876543210.'],
            WelcomeWhatsApp::resend(User::find(10), 'student')
        );
        $this->assertCount(1, $this->smsSent());
        $this->assertSame(0, $this->whatsAppSent());
        $this->assertSame('credentials', DB::table('sms_messages')->value('feature'));

        // Still the super admin's Send Credentials switch first.
        DB::table('whatsapp_school_features')->where('organization_id', 3)->update(['enabled' => false]);
        $this->assertFalse(WelcomeWhatsApp::resend(User::find(10), 'student')['ok']);
    }

    public function test_an_old_account_without_a_readable_password_is_told_to_keep_its_own(): void
    {
        SchoolSms::setEnabled(4, 'welcome', true);

        WelcomeWhatsApp::student(30);

        $this->assertStringContainsString('Password: ' . WelcomeSms::PASSWORD_UNKNOWN . '.', $this->smsSent()[0]['text']);
    }

    public function test_a_refusal_is_kept_as_failed_without_any_password(): void
    {
        $this->bhash = 'Invalid Login';
        $this->assertFalse(SchoolSms::send(3, 'welcome', '9876543210', 'student_welcome', ['A', 'B', 'C', 'D', 'E', 'pw']));
        $this->assertSame(['failed', 'Invalid Login'], [DB::table('sms_messages')->value('status'), DB::table('sms_messages')->value('error')]);
    }

    public function test_an_unreachable_bhash_is_logged_without_the_query(): void
    {
        $this->bhash = new ConnectionException(
            'cURL error 28: Operation timed out for https://bhashsms.com/api/sendmsg.php?user=superlms&pass=bhash-secret&text=Password%3A+Xk9'
        );

        $result = SmsService::send('9876543210', 'student_welcome', ['A', 'B', 'C', 'D', 'E', 'Xk9']);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('cURL error 28', $result['error']);
        $this->assertStringNotContainsString('bhash-secret', $result['error']);
        $this->assertStringNotContainsString('Xk9', $result['error']);
    }

    public function test_the_welcome_email_is_not_sent_for_a_school_on_sms(): void
    {
        $mail = function (string $controller, User $user, int $orgId, array $args) {
            $c = $this->app->make($controller);
            $m = new \ReflectionMethod($c, 'sendWelcomeEmail');
            $m->setAccessible(true);
            $m->invoke($c, $user, $orgId, ...$args);
            $this->app->terminate();
        };
        $zepto = fn () => collect(Http::recorded())->filter(fn ($p) => str_contains($p[0]->url(), 'zeptomail'))->count();

        $mail(\App\Http\Controllers\v1\AdminStudentController::class, User::find(10), 3, ['pw', '26APS100001']);
        $mail(\App\Http\Controllers\v1\AdminTeacherController::class, User::find(20), 3, ['pw']);
        $this->assertSame(0, $zepto());

        // A school not on SMS still gets it.
        $mail(\App\Http\Controllers\v1\AdminStudentController::class, User::find(30), 4, ['pw', '26BS100001']);
        $this->assertSame(1, $zepto());
    }

    public function test_the_app_link_goes_to_the_play_store(): void
    {
        config(['services.whatsapp.app_url' => 'https://play.google.com/store/apps/details?id=com.edyoneapp']);

        $this->get('/app')->assertRedirect('https://play.google.com/store/apps/details?id=com.edyoneapp');
    }

    public function test_the_super_admin_sees_the_switch_and_what_went(): void
    {
        WelcomeWhatsApp::student(10);
        $this->smsSent();

        $s = SchoolSms::summary(3);
        $this->assertTrue($s['enabled']);
        $this->assertTrue($s['configured']);
        $this->assertSame(['sent' => 1, 'failed' => 0, 'parts' => 2], $s['month']);
        $this->assertSame(['Asha Verma', 'Welcome', 'Student', '+91 9876543210', 'sent'],
            [$s['messages'][0]['name'], $s['messages'][0]['kind'], $s['messages'][0]['who'], $s['messages'][0]['to'], $s['messages'][0]['status']]);

        $this->assertFalse(SchoolSms::summary(4)['enabled']);
    }
}
