<?php

namespace Tests\Feature;

use App\Models\Student\StudentDetail;
use App\Models\WhatsAppMessage;
use App\Services\SchoolWhatsApp;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The WhatsApp messages a school pays for (App\Services\SchoolWhatsApp): the
 * absent alert and the fee received go only once the Super Admin turns them
 * on, each thing once; every message is kept with its rate (the welcome
 * free); a month's usage counts what went and charges the paid ones; Meta's
 * status webhook moves a message on, and one that failed is not charged.
 */
class SchoolWhatsAppTest extends TestCase
{
    private const SECRET = 'app-secret';

    private int $sentCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('w', 32))]);
        Carbon::setTestNow('2026-10-15 10:00:00');

        config([
            'services.whatsapp.enabled'              => true,
            'services.whatsapp.token'                => 'test-token',
            'services.whatsapp.phone_number_id'      => '1294658720402506',
            'services.whatsapp.webhook_verify_token' => 'verify-me',
            'services.whatsapp.app_secret'           => self::SECRET,
        ]);
        Http::fake(['graph.facebook.com/*' => fn () => Http::response(['messages' => [['id' => 'wamid.' . ++$this->sentCount]]])]);

        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('mobile_number')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->timestamps(); });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->date('attendance_date'); $t->tinyInteger('status'); $t->string('remarks')->nullable(); $t->unsignedBigInteger('marked_by')->nullable(); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable(); $t->string('fee_name')->nullable(); $t->string('fee_type')->default('academic');
            $t->decimal('amount', 12, 2)->default(0); $t->string('academic_year')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->string('fee_type')->default('academic');
            $t->string('applies_to')->nullable(); $t->decimal('amount', 12, 2)->default(0); $t->string('payment_mode')->nullable(); $t->string('receipt_number')->nullable();
            $t->date('payment_date')->nullable(); $t->timestamps();
        });
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->string('fee_type')->default('all');
            $t->string('concession_type')->default('amount'); $t->decimal('value', 12, 2)->default(0); $t->boolean('is_penalty')->default(false); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->decimal('monthly_fee', 12, 2)->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id'); $t->unsignedBigInteger('student_detail_id');
            $t->text('billable_months')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->decimal('amount', 12, 2)->default(0);
            $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable(); $t->timestamps();
        });
        app()->forgetInstance('fee_structures.student_rows');

        // The real migration: the messages and the schools' switches.
        (require base_path('database/migrations/2026_10_14_200000_create_whatsapp_messages_tables.php'))->up();

        DB::table('organizations')->insert([['id' => 1, 'name' => 'Sunrise Public School'], ['id' => 2, 'name' => 'Other School']]);
        DB::table('standards')->insert(['id' => 1, 'organization_id' => 1, 'name' => 'CLASS 9']);
        DB::table('sections')->insert(['id' => 1, 'organization_id' => 1, 'standard_id' => 1, 'name' => 'A']);
        DB::table('users')->insert([
            ['id' => 10, 'name' => 'Asha', 'mobile_number' => '9876543210', 'organization_id' => 1],
            ['id' => 11, 'name' => 'Bala', 'mobile_number' => '9123456780', 'organization_id' => 1],
            ['id' => 12, 'name' => 'Chand', 'mobile_number' => '9000000001', 'organization_id' => 2],
        ]);
        DB::table('student_details')->insert([
            ['id' => 1, 'organization_id' => 1, 'user_id' => 10, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Asha Verma', 'phone' => null],
            ['id' => 2, 'organization_id' => 1, 'user_id' => 11, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Bala Rao', 'phone' => '9988776655'],
            ['id' => 3, 'organization_id' => 2, 'user_id' => 12, 'standard_id' => null, 'section_id' => null, 'full_name' => 'Chand', 'phone' => null],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function student(int $id): StudentDetail
    {
        return StudentDetail::with(['user', 'standard', 'section'])->find($id);
    }

    /** The body parameters of each template sent, in order. */
    private function sentBodies(): array
    {
        return Http::recorded()->map(fn ($pair) => [
            $pair[0]['template']['name'],
            $pair[0]['to'],
            array_column($pair[0]['template']['components'][0]['parameters'] ?? [], 'text'),
        ])->values()->all();
    }

    // ─── Sending and keeping ──────────────────────────────────────────────────

    public function test_a_charged_message_is_kept_with_its_rate_and_the_welcome_is_free(): void
    {
        $this->assertTrue(SchoolWhatsApp::send(1, 'credentials', '9876543210', 'admission_confirmed', ['A'], null, ['user_id' => 10]));
        $this->assertTrue(SchoolWhatsApp::send(1, 'welcome', '9876543210', 'admission_confirmed', ['A'], null, ['user_id' => 10]));

        [$paid, $free] = WhatsAppMessage::orderBy('id')->get()->all();
        $this->assertSame(['credentials', 'sent', true, '0.66', 'wamid.1', '919876543210'], [$paid->feature, $paid->status, $paid->billable, $paid->rate, $paid->wa_message_id, $paid->to_phone]);
        $this->assertSame(['welcome', false, '0.00'], [$free->feature, $free->billable, $free->rate]);
    }

    public function test_a_message_meta_refuses_is_kept_as_failed(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory(app('events')));   // in place of setUp's fake
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not approved']], 400)]);

        $this->assertFalse(SchoolWhatsApp::send(1, 'credentials', '9876543210', 'admission_confirmed', ['A']));
        $row = WhatsAppMessage::first();
        $this->assertSame(['failed', 'Template not approved', null], [$row->status, $row->error, $row->wa_message_id]);
        $this->assertSame(0, SchoolWhatsApp::usage(1, '2026-10')['charged']);
    }

    public function test_no_number_means_nothing_sent_or_kept(): void
    {
        $this->assertFalse(SchoolWhatsApp::send(1, 'credentials', '123', 'admission_confirmed', ['A']));
        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppMessage::count());
    }

    // ─── Absent alert ─────────────────────────────────────────────────────────

    public function test_paid_messages_are_off_until_turned_on_and_only_the_switched_ones_switch(): void
    {
        $this->assertFalse(SchoolWhatsApp::absentAlert($this->student(1), '2026-10-15'));
        Http::assertNothingSent();

        SchoolWhatsApp::setEnabled(1, 'absent_alert', true);
        SchoolWhatsApp::setEnabled(1, 'credentials', true);   // not a switched message: ignored
        $this->assertTrue(SchoolWhatsApp::enabled(1, 'absent_alert'));
        $this->assertFalse(SchoolWhatsApp::enabled(1, 'fee_received'));
        $this->assertFalse(SchoolWhatsApp::enabled(2, 'absent_alert'));          // another school
        $this->assertSame(1, DB::table('whatsapp_school_features')->count());

        $this->assertTrue(SchoolWhatsApp::absentAlert($this->student(1), '2026-10-15'));
        SchoolWhatsApp::setEnabled(1, 'absent_alert', false);
        $this->assertFalse(SchoolWhatsApp::absentAlert($this->student(1), '2026-10-16'));
        $this->assertSame(1, WhatsAppMessage::count());
    }

    public function test_the_absent_alert_names_the_student_class_day_and_school_once_a_day(): void
    {
        SchoolWhatsApp::setEnabled(1, 'absent_alert', true);

        $this->assertTrue(SchoolWhatsApp::absentAlert($this->student(1), '2026-10-15'));
        $this->assertFalse(SchoolWhatsApp::absentAlert($this->student(1), '2026-10-15'));   // the same day again
        $this->assertTrue(SchoolWhatsApp::absentAlert($this->student(2), '2026-10-15'));

        $this->assertSame([
            // Asha has no phone of her own: her account's.
            ['student_absent_alert', '919876543210', ['Asha Verma', 'CLASS 9 - A', '15 Oct 2026', 'Sunrise Public School']],
            ['student_absent_alert', '919988776655', ['Bala Rao', 'CLASS 9 - A', '15 Oct 2026', 'Sunrise Public School']],
        ], $this->sentBodies());
        $this->assertSame('absent_alert:1:2026-10-15', WhatsAppMessage::orderBy('id')->value('dedupe_key'));
        $this->assertTrue(SchoolWhatsApp::sentFor('absent_alert:1:2026-10-15'));
    }

    public function test_the_command_alerts_an_absent_mark_two_minutes_after_it_is_saved_at_any_hour(): void
    {
        SchoolWhatsApp::setEnabled(1, 'absent_alert', true);
        $at = fn (string $time) => ['created_at' => $time, 'updated_at' => $time];
        // Who marked it (teacher, admin, sub admin, accounts) makes no difference.
        DB::table('student_attendances')->insert([
            ['organization_id' => 1, 'student_detail_id' => 1, 'attendance_date' => '2026-10-15', 'status' => 0, 'marked_by' => 10] + $at('2026-10-15 09:58:00'),
            // Marked absent a minute ago: not yet (it may be put right).
            ['organization_id' => 1, 'student_detail_id' => 2, 'attendance_date' => '2026-10-15', 'status' => 0, 'marked_by' => 11] + $at('2026-10-15 09:59:00'),
            // Absent in a school that has it off.
            ['organization_id' => 2, 'student_detail_id' => 3, 'attendance_date' => '2026-10-15', 'status' => 0, 'marked_by' => 12] + $at('2026-10-15 09:00:00'),
        ]);

        Artisan::call('whatsapp:absent-alerts');
        Artisan::call('whatsapp:absent-alerts');
        $this->assertSame([1], WhatsAppMessage::pluck('student_detail_id')->all());

        Carbon::setTestNow('2026-10-15 10:01:00');
        Artisan::call('whatsapp:absent-alerts');
        $this->assertSame([1, 2], WhatsAppMessage::orderBy('id')->pluck('student_detail_id')->all());

        // Late at night too; a mark put right to present within the two minutes gets nothing.
        DB::table('student_attendances')->insert([
            ['organization_id' => 1, 'student_detail_id' => 1, 'attendance_date' => '2026-10-16', 'status' => 0] + $at('2026-10-16 23:30:00'),
            ['organization_id' => 1, 'student_detail_id' => 2, 'attendance_date' => '2026-10-16', 'status' => 1] + $at('2026-10-16 23:30:40'),
        ]);
        Carbon::setTestNow('2026-10-16 23:32:00');
        Artisan::call('whatsapp:absent-alerts');
        $this->assertSame(['absent_alert:1:2026-10-16'], WhatsAppMessage::where('dedupe_key', 'like', '%2026-10-16')->pluck('dedupe_key')->all());
    }

    public function test_the_alerts_run_every_minute(): void
    {
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'whatsapp:absent-alerts'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }

    // ─── Fee received ─────────────────────────────────────────────────────────

    public function test_the_fee_message_reads_like_the_receipt(): void
    {
        DB::table('fee_structures')->insert(['organization_id' => 1, 'standard_id' => 1, 'fee_name' => 'Tuition', 'fee_type' => 'academic', 'amount' => 12000]);
        DB::table('fee_payments')->insert([
            ['id' => 1, 'organization_id' => 1, 'student_detail_id' => 2, 'fee_type' => 'academic', 'applies_to' => null, 'amount' => 2500, 'payment_mode' => 'cash', 'payment_date' => '2026-10-15'],
            ['id' => 2, 'organization_id' => 1, 'student_detail_id' => 2, 'fee_type' => 'academic', 'applies_to' => 'dues', 'amount' => 499.5, 'payment_mode' => 'upi', 'payment_date' => '2026-10-15'],
        ]);

        $this->assertTrue(SchoolWhatsApp::sendFeeReceived('fee', 1));
        $this->assertFalse(SchoolWhatsApp::sendFeeReceived('fee', 1));   // one message a payment
        $this->assertTrue(SchoolWhatsApp::sendFeeReceived('fee', 2));

        $this->assertSame([
            ['fee_received', '919988776655', ['Sunrise Public School', 'Bala Rao', 'CLASS 9 - A', 'Academic', '2,500', '15 Oct 2026', '12,000', '9,000.50']],
            ['fee_received', '919988776655', ['Sunrise Public School', 'Bala Rao', 'CLASS 9 - A', 'Last Year Dues', '499.50', '15 Oct 2026', '12,000', '9,000.50']],
        ], $this->sentBodies());
        $this->assertSame(['fee_received:fee:1', 'fee_received:fee:2'], WhatsAppMessage::orderBy('id')->pluck('dedupe_key')->all());
    }

    public function test_a_fee_booked_goes_on_whatsapp_only_where_it_is_on_and_never_for_a_concession(): void
    {
        $book = fn (array $row) => \App\Models\Admin\Fee\FeePayment::forceCreate($row + [
            'organization_id' => 1, 'student_detail_id' => 2, 'fee_type' => 'academic', 'payment_date' => '2026-10-15',
        ]);

        $book(['amount' => 100, 'payment_mode' => 'cash']);
        $this->app->terminate();                       // the after-response work
        Http::assertNothingSent();

        SchoolWhatsApp::setEnabled(1, 'fee_received', true);
        $book(['amount' => 0, 'payment_mode' => 'cash']);
        $book(['amount' => 50, 'payment_mode' => 'concession']);
        $book(['amount' => 300, 'payment_mode' => 'cash']);
        $this->app->terminate();

        $this->assertSame(['fee_received:fee:4'], WhatsAppMessage::pluck('dedupe_key')->all());
    }

    // ─── Usage ────────────────────────────────────────────────────────────────

    private function message(string $feature, string $status, string $at, int $org = 1): void
    {
        $charged = SchoolWhatsApp::FEATURES[$feature][1];
        WhatsAppMessage::forceCreate([
            'organization_id' => $org, 'feature' => $feature, 'template' => 't', 'status' => $status,
            'billable' => $charged, 'rate' => $charged ? 0.66 : 0, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    public function test_a_months_usage_counts_what_went_and_charges_only_the_paid_ones(): void
    {
        $this->message('absent_alert', 'sent', '2026-10-01 08:00:00');
        $this->message('absent_alert', 'read', '2026-10-31 23:59:00');
        $this->message('fee_received', 'delivered', '2026-10-10 12:00:00');
        $this->message('credentials', 'sent', '2026-10-11 12:00:00');
        $this->message('welcome', 'sent', '2026-10-12 12:00:00');
        $this->message('absent_alert', 'failed', '2026-10-13 12:00:00');     // not delivered: not charged
        $this->message('fee_received', 'sending', '2026-10-14 12:00:00');    // never went
        $this->message('absent_alert', 'sent', '2026-09-30 12:00:00');       // another month
        $this->message('absent_alert', 'sent', '2026-10-05 12:00:00', 2);    // another school

        $this->assertSame([
            'counts'  => ['absent_alert' => 2, 'fee_received' => 1, 'credentials' => 1, 'welcome' => 1],
            'charged' => 4,
            'amount'  => 2.64,
            'failed'  => 1,
        ], SchoolWhatsApp::usage(1, '2026-10'));
        $this->assertSame(1, SchoolWhatsApp::usage(1, '2026-09')['charged']);
        $this->assertSame(0, SchoolWhatsApp::usage(1, 'nonsense')['charged']);

        $summary = SchoolWhatsApp::summary(1, 'nonsense');
        $this->assertSame(['2026-10' => 'Oct 2026', '2026-09' => 'Sep 2026'], $summary['months']);
        $this->assertSame('2026-10', $summary['month']);                       // this month when the one asked is not there
        $this->assertSame(['absent_alert' => false, 'fee_received' => false], $summary['switches']);
        $this->assertSame(0.66, $summary['rate']);
        $this->assertSame('2026-09', SchoolWhatsApp::summary(1, '2026-09')['month']);

        $this->assertTrue(SchoolWhatsApp::inUse(1));
        $this->assertFalse(SchoolWhatsApp::inUse(3));
    }

    // ─── Meta's status webhook ────────────────────────────────────────────────

    private function statuses(array $statuses): array
    {
        return ['object' => 'whatsapp_business_account', 'entry' => [[
            'id'      => 'WABA',
            'changes' => [['field' => 'messages', 'value' => ['messaging_product' => 'whatsapp', 'statuses' => $statuses]]],
        ]]];
    }

    private function postSigned(array $payload, ?string $secret = self::SECRET)
    {
        $body = json_encode($payload);
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($secret !== null) {
            $headers['HTTP_X_HUB_SIGNATURE_256'] = 'sha256=' . hash_hmac('sha256', $body, $secret);
        }

        return $this->call('POST', '/api/whatsapp/webhook', [], [], [], $headers, $body);
    }

    public function test_the_callback_is_verified_with_its_token(): void
    {
        $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=12345')
            ->assertOk()->assertSee('12345', false);
        $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345')->assertForbidden();

        config(['services.whatsapp.webhook_verify_token' => null]);
        $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=&hub.challenge=12345')->assertForbidden();
    }

    public function test_a_post_not_signed_with_the_app_secret_changes_nothing(): void
    {
        SchoolWhatsApp::send(1, 'credentials', '9876543210', 'admission_confirmed', ['A']);
        $failed = $this->statuses([['id' => 'wamid.1', 'status' => 'failed']]);

        $this->postSigned($failed, null)->assertStatus(401);
        $this->postSigned($failed, 'someone-elses')->assertStatus(401);
        config(['services.whatsapp.app_secret' => null]);
        $this->postSigned($failed, '')->assertStatus(401);

        $this->assertSame('sent', WhatsAppMessage::first()->status);
    }

    public function test_statuses_move_a_message_on_and_a_failed_one_is_not_charged(): void
    {
        SchoolWhatsApp::send(1, 'credentials', '9876543210', 'admission_confirmed', ['A']);   // wamid.1
        SchoolWhatsApp::send(1, 'credentials', '9123456780', 'admission_confirmed', ['B']);   // wamid.2
        $this->assertSame(1.32, SchoolWhatsApp::usage(1, '2026-10')['amount']);

        $this->postSigned($this->statuses([
            ['id' => 'wamid.1', 'status' => 'delivered', 'timestamp' => '1760500000', 'recipient_id' => '919876543210'],
            ['id' => 'wamid.2', 'status' => 'failed', 'errors' => [[
                'code' => 131026, 'title' => 'Message undeliverable', 'error_data' => ['details' => 'Not on WhatsApp'],
            ]]],
            ['id' => 'wamid.not-ours', 'status' => 'read'],
        ]))->assertOk()->assertJson(['success' => true, 'updated' => 2]);

        $rows = WhatsAppMessage::orderBy('id')->get();
        $this->assertSame('delivered', $rows[0]->status);
        $this->assertSame(['failed', '131026 Message undeliverable — Not on WhatsApp'], [$rows[1]->status, $rows[1]->error]);

        $usage = SchoolWhatsApp::usage(1, '2026-10');
        $this->assertSame([1, 0.66, 1], [$usage['charged'], $usage['amount'], $usage['failed']]);
    }

    public function test_a_status_never_takes_a_message_back(): void
    {
        SchoolWhatsApp::send(1, 'credentials', '9876543210', 'admission_confirmed', ['A']);   // wamid.1
        SchoolWhatsApp::send(1, 'credentials', '9123456780', 'admission_confirmed', ['B']);   // wamid.2
        $status = fn () => WhatsAppMessage::orderBy('id')->pluck('status')->all();

        // Read, then a late delivered and a failed: it stays read (it reached the phone).
        SchoolWhatsApp::applyStatus('wamid.1', 'read');
        $this->assertFalse(SchoolWhatsApp::applyStatus('wamid.1', 'delivered'));
        $this->assertFalse(SchoolWhatsApp::applyStatus('wamid.1', 'failed', 'late'));
        // Failed, then a late sent: still failed; delivered after all: charged again.
        SchoolWhatsApp::applyStatus('wamid.2', 'failed', 'Undeliverable');
        $this->assertFalse(SchoolWhatsApp::applyStatus('wamid.2', 'sent'));
        $this->assertSame(['read', 'failed'], $status());
        $this->assertTrue(SchoolWhatsApp::applyStatus('wamid.2', 'delivered'));
        $this->assertSame(['read', 'delivered'], $status());
        $this->assertNull(WhatsAppMessage::find(2)->error);

        // Statuses we do not keep, and messages not ours.
        $this->assertFalse(SchoolWhatsApp::applyStatus('wamid.1', 'deleted'));
        $this->assertFalse(SchoolWhatsApp::applyStatus('wamid.unknown', 'read'));
        $this->assertFalse(SchoolWhatsApp::applyStatus('', 'read'));
    }
}
