<?php

namespace Tests\Feature;

use App\Http\Controllers\PhonePeController;
use App\Http\Controllers\v1\AutopayController;
use App\Livewire\Admin\Fee;
use App\Livewire\Admin\FeeAutopay as FeeAutopayTab;
use App\Models\Admin\Fee\FeeAutopayDebit;
use App\Models\Admin\Fee\FeeAutopayMandate;
use App\Models\Admin\Fee\FeePayment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\FeeAutopay;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fee Autopay (PhonePe UPI AutoPay): a parent sets up a mandate in the app;
 * each installment due in the next two days is told to them through PhonePe,
 * taken 24 hours later in PhonePe's debit hours if still owed, and settles
 * into an ordinary online fee payment. Only where the Super Admin turned it
 * on for the school's own PhonePe merchant.
 */
class FeeAutopayTest extends TestCase
{
    private int $org = 7;
    private int $student;
    private User $parent;

    /** What the fake PhonePe answers. */
    private string $subState   = 'ACTIVE';
    private string $orderState = 'NOTIFIED';
    private string $redeemState = 'PENDING';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
        Carbon::setTestNow('2026-11-02 10:00:00');

        $tracked = new \ReflectionProperty(FeeAutopay::class, 'tracked');
        $tracked->setValue(null, null);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('image')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->text('permissions')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('school_code')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->integer('order')->nullable(); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id');
            $t->string('full_name')->nullable(); $t->string('admission_no')->nullable(); $t->string('roll_no')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type'); $t->string('fee_name')->nullable(); $t->decimal('amount', 12, 2); $t->boolean('is_active')->default(true);
            $t->string('academic_year')->nullable(); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type'); $t->decimal('amount', 12, 2); $t->decimal('waiver_amount', 12, 2)->nullable(); $t->string('waiver_reason')->nullable();
            $t->decimal('penalty_amount', 12, 2)->nullable(); $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable();
            $t->text('remark')->nullable(); $t->string('submitted_by')->nullable(); $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->string('fee_type'); $t->string('concession_type'); $t->decimal('value', 12, 2); $t->string('reason')->nullable();
            $t->string('academic_year')->nullable(); $t->boolean('is_penalty')->default(false); $t->unsignedBigInteger('fee_cycle_id')->nullable(); $t->timestamps();
        });
        Schema::create('fee_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type'); $t->boolean('is_active')->default(true);
            $t->string('academic_year')->nullable(); $t->integer('payment_serial')->nullable(); $t->decimal('fee_percent', 5, 2)->default(0);
            $t->decimal('amount', 12, 2)->default(0); $t->date('due_date')->nullable(); $t->decimal('penalty_per_day', 8, 2)->default(0);
            $t->boolean('is_token')->default(false); $t->timestamps();
        });
        Schema::create('fee_payment_requests', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('user_id');
            $t->string('fee_type', 20); $t->decimal('amount', 10, 2); $t->date('paid_on'); $t->string('status', 20)->default('pending'); $t->timestamps();
        });
        (require base_path('database/migrations/2026_06_16_000002_create_payment_transactions_table.php'))->up();
        (require base_path('database/migrations/2026_06_17_000001_create_organization_payment_settings_table.php'))->up();
        (require base_path('database/migrations/2026_11_02_140000_create_fee_autopay_tables.php'))->up();

        // Pushes are caught, never sent.
        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public array $sent = [];
            public function notifyUser($user, $type, $opts = []) { $this->sent[] = [$user?->id, $type, $opts]; return true; }
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'DPS', 'school_code' => 'DPS']);
        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'CLASS 5']);
        $sec = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'name' => 'A']);
        $this->parent = User::forceCreate(['name' => 'Aarav', 'role' => 'user', 'organization_id' => $this->org]);
        $this->student = DB::table('student_details')->insertGetId([
            'user_id' => $this->parent->id, 'organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec,
            'full_name' => 'Aarav Sharma', 'admission_no' => '26DPS1',
        ]);
        DB::table('fee_structures')->insert(['organization_id' => $this->org, 'standard_id' => $std, 'fee_type' => 'academic', 'fee_name' => 'Tuition', 'amount' => 10000]);
        DB::table('fee_cycles')->insert([
            ['id' => 1, 'organization_id' => $this->org, 'fee_type' => 'academic', 'payment_serial' => 1, 'fee_percent' => 50, 'due_date' => '2026-11-04'],
            ['id' => 2, 'organization_id' => $this->org, 'fee_type' => 'academic', 'payment_serial' => 2, 'fee_percent' => 50, 'due_date' => '2026-12-15'],
        ]);

        \App\Models\OrganizationPaymentSetting::create([
            'organization_id' => $this->org, 'gateway' => 'phonepe', 'client_id' => 'CID', 'client_secret' => 'SECRET',
            'client_version' => '1', 'env' => 'sandbox', 'webhook_username' => 'hook', 'webhook_password' => 'pass',
            'is_active' => true, 'autopay_enabled' => true,
        ]);

        Http::fake(function (HttpRequest $r) {
            $path = parse_url($r->url(), PHP_URL_PATH);

            return match (true) {
                str_ends_with($path, '/v1/oauth/token')       => Http::response(['access_token' => 'tok', 'expires_at' => now()->addHour()->timestamp]),
                str_ends_with($path, '/checkout/v2/pay')      => Http::response(['orderId' => 'OMO1', 'state' => 'PENDING', 'redirectUrl' => 'https://mercury.phonepe.test/approve/1']),
                str_ends_with($path, '/subscriptions/v2/notify') => Http::response(['orderId' => 'OMN1', 'state' => 'NOTIFICATION_IN_PROGRESS']),
                str_ends_with($path, '/subscriptions/v2/redeem') => Http::response(['state' => $this->redeemState, 'transactionId' => 'T1']),
                str_ends_with($path, '/cancel')               => Http::response(null, 204),
                str_contains($path, '/subscriptions/v2/order/') => Http::response(['state' => $this->orderState]),
                str_contains($path, '/subscriptions/v2/')     => Http::response(['state' => $this->subState, 'subscriptionId' => 'SUB1']),
                default                                       => Http::response(['message' => 'unexpected ' . $path], 500),
            };
        });

        Auth::setUser($this->parent);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function api(string $method, array $query = []): array
    {
        $res = app(AutopayController::class)->{$method}(Request::create('/x', 'GET', $query));

        return ['status' => $res->getStatusCode()] + $res->getData(true);
    }

    private function calls(string $pathEnd): array
    {
        return Http::recorded(fn (HttpRequest $r) => str_ends_with(parse_url($r->url(), PHP_URL_PATH), $pathEnd))
            ->map(fn ($p) => $p[0])->values()->all();
    }

    private function webhook(string $event, array $payload, ?string $auth = null)
    {
        $req = Request::create('/api/v1/phonepe/webhook', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => $auth ?? hash('sha256', 'hook:pass'),
        ], json_encode(['event' => $event, 'payload' => $payload]));

        return app(PhonePeController::class)->handleWebhook($req);
    }

    /** Set up and activate a mandate. */
    private function activeMandate(): FeeAutopayMandate
    {
        $this->api('setup');
        $m = FeeAutopayMandate::firstOrFail();
        $this->webhook('subscription.setup.order.completed', ['merchantOrderId' => $m->setup_order_id, 'state' => 'COMPLETED']);

        return $m->fresh();
    }

    private function pushes(): array
    {
        return app(\App\Services\FirebaseNotificationService::class)->sent;
    }

    public function test_the_app_offers_autopay_and_sets_it_up_on_phonepes_page(): void
    {
        $show = $this->api('show')['data'];
        $this->assertTrue($show['available']);
        $this->assertNull($show['mandate']);
        $this->assertSame(5000.0, (float) $show['suggested_max']);   // the largest installment
        $this->assertSame(['1st installment', '2nd installment'], array_column($show['upcoming'], 'label'));

        $setup = $this->api('setup');
        $this->assertSame(200, $setup['status']);
        $this->assertSame('https://mercury.phonepe.test/approve/1', $setup['data']['redirect_url']);

        $m = FeeAutopayMandate::firstOrFail();
        $this->assertSame('pending', $m->status);
        $this->assertSame('5000.00', $m->max_amount);
        $this->assertSame('2027-04-30', $m->expires_at->toDateString());

        [$pay] = $this->calls('/checkout/v2/pay');
        $flow = $pay['paymentFlow'];
        $this->assertSame('SUBSCRIPTION_CHECKOUT_SETUP', $flow['type']);
        $this->assertSame(200, $pay['amount']);
        $this->assertSame('PENNY_DROP', $flow['subscriptionDetails']['authWorkflowType']);
        $this->assertSame('VARIABLE', $flow['subscriptionDetails']['amountType']);
        $this->assertSame('ON_DEMAND', $flow['subscriptionDetails']['frequency']);
        $this->assertSame(500000, $flow['subscriptionDetails']['maxAmount']);
        $this->assertSame($m->merchant_subscription_id, $flow['subscriptionDetails']['merchantSubscriptionId']);
        $this->assertStringEndsWith('/autopay/return/' . $m->setup_order_id, $flow['merchantUrls']['redirectUrl']);

        // Approved: PhonePe's webhook turns it on (a wrong signature is refused).
        $this->assertSame(401, $this->webhook('subscription.setup.order.completed', ['merchantOrderId' => $m->setup_order_id], 'bad')->getStatusCode());
        $this->assertSame('pending', $m->fresh()->status);
        $this->webhook('subscription.setup.order.completed', ['merchantOrderId' => $m->setup_order_id, 'state' => 'COMPLETED']);
        $m->refresh();
        $this->assertSame('active', $m->status);
        $this->assertSame('SUB1', $m->phonepe_subscription_id);

        $show = $this->api('show')['data'];
        $this->assertSame('active', $show['mandate']['status']);
        $this->assertSame('422', (string) $this->api('setup')['status']);   // already on
    }

    public function test_a_school_without_autopay_on_its_own_merchant_offers_none(): void
    {
        DB::table('organization_payment_settings')->update(['autopay_enabled' => false]);
        $this->assertFalse($this->api('show')['data']['available']);
        $this->assertSame(422, $this->api('setup')['status']);

        // Autopay on, but collection off: it would be the platform's account — never.
        DB::table('organization_payment_settings')->update(['autopay_enabled' => true, 'is_active' => false]);
        $this->assertFalse(FeeAutopay::available($this->org));
        $this->assertSame([], $this->calls('/checkout/v2/pay'));
    }

    public function test_an_installment_is_told_two_days_ahead_taken_24_hours_later_and_paid(): void
    {
        $m = $this->activeMandate();

        // 2 Nov: the 1st installment (due 4 Nov) is told to the parent; the 2nd (December) is not.
        $this->assertSame(1, FeeAutopay::run()['notified']);
        $debit = FeeAutopayDebit::firstOrFail();
        $this->assertSame(1, (int) $debit->fee_cycle_id);
        $this->assertSame('5000.00', $debit->amount);
        $this->assertSame('notifying', $debit->status);
        $this->assertSame('2026-11-03 10:10', $debit->valid_after->format('Y-m-d H:i'));

        [$notify] = $this->calls('/subscriptions/v2/notify');
        $this->assertSame(500000, $notify['amount']);
        $this->assertSame($debit->merchant_order_id, $notify['merchantOrderId']);
        $this->assertSame('SUBSCRIPTION_REDEMPTION', $notify['paymentFlow']['type']);
        $this->assertSame($m->merchant_subscription_id, $notify['paymentFlow']['merchantSubscriptionId']);
        $this->assertFalse($notify['paymentFlow']['autoDebit']);

        [$userId, $type, $opts] = $this->pushes()[0];
        $this->assertSame($this->parent->id, $userId);
        $this->assertSame('fee_due', $type);
        $this->assertStringContainsString('1st installment of ₹5,000 will be paid by Autopay from your bank on 03 Nov 2026', $opts['body']);

        // Run again: told once only.
        $this->assertSame(0, FeeAutopay::run()['notified']);
        $this->assertCount(1, $this->calls('/subscriptions/v2/notify'));

        // PhonePe's notification webhook.
        $this->webhook('subscription.notification.completed', ['merchantOrderId' => $debit->merchant_order_id, 'state' => 'NOTIFIED']);
        $this->assertSame('notified', $debit->fresh()->status);

        // 3 Nov 11:00 — 24 hours are up, but it's PhonePe's busy hours: not yet.
        Carbon::setTestNow('2026-11-03 11:00:00');
        $this->assertSame(0, FeeAutopay::run()['debited']);
        // 14:00 — taken.
        Carbon::setTestNow('2026-11-03 14:00:00');
        $this->assertSame(1, FeeAutopay::run()['debited']);
        $this->assertSame('debiting', $debit->fresh()->status);
        $this->assertSame([$debit->merchant_order_id], array_map(fn ($r) => $r['merchantOrderId'], $this->calls('/subscriptions/v2/redeem')));

        // Paid: an ordinary online fee payment, said to be Autopay.
        $this->webhook('subscription.redemption.order.completed', ['merchantOrderId' => $debit->merchant_order_id, 'state' => 'COMPLETED']);
        $debit->refresh();
        $this->assertSame('completed', $debit->status);
        $fee = FeePayment::firstOrFail();
        $this->assertSame('5000.00', (string) $fee->amount);
        $this->assertSame('online', $fee->payment_mode);
        $this->assertSame('PhonePe Autopay', $fee->submitted_by);
        $this->assertStringContainsString('Autopay via PhonePe — 1st installment', $fee->remark);
        $this->assertSame(PaymentTransaction::STATE_COMPLETED, $debit->transaction->state);
        $this->assertSame($fee->id, $debit->transaction->fee_payment_id);

        // A second word of it changes nothing.
        $this->webhook('subscription.redemption.transaction.completed', ['merchantOrderId' => $debit->merchant_order_id, 'state' => 'COMPLETED']);
        $this->assertSame(1, FeePayment::count());

        $paid = $this->api('show')['data'];
        $this->assertSame(['2nd installment'], array_column($paid['upcoming'], 'label'));
        $this->assertSame('completed', $paid['debits'][0]['status']);
    }

    public function test_paid_some_other_way_meanwhile_it_is_not_taken(): void
    {
        $this->activeMandate();
        FeeAutopay::run();
        $debit = FeeAutopayDebit::firstOrFail();

        // The parent paid at the counter after being told.
        FeePayment::create(['organization_id' => $this->org, 'student_detail_id' => $this->student, 'fee_type' => 'academic',
            'amount' => 5000, 'payment_mode' => 'cash', 'payment_date' => '2026-11-02']);

        Carbon::setTestNow('2026-11-03 14:00:00');
        FeeAutopay::run();

        $this->assertSame('skipped', $debit->fresh()->status);
        $this->assertSame([], $this->calls('/subscriptions/v2/redeem'));
        $this->assertSame(PaymentTransaction::STATE_FAILED, $debit->fresh()->transaction->state);
    }

    public function test_a_debit_is_capped_at_the_mandates_maximum_and_a_failed_one_is_said(): void
    {
        $m = $this->activeMandate();
        $m->update(['max_amount' => 3000]);

        FeeAutopay::run();
        $debit = FeeAutopayDebit::firstOrFail();
        $this->assertSame('3000.00', $debit->amount);   // the rest is the parent's to pay

        Carbon::setTestNow('2026-11-03 14:00:00');
        FeeAutopay::run();
        $this->webhook('subscription.redemption.order.failed', ['merchantOrderId' => $debit->merchant_order_id, 'state' => 'FAILED', 'errorCode' => 'INSUFFICIENT_BALANCE']);

        $debit->refresh();
        $this->assertSame('failed', $debit->status);
        $this->assertSame('Debit failed: insufficient balance', $debit->failure_reason);
        $this->assertSame(0, FeePayment::count());
        $pushes = $this->pushes();
        $this->assertSame('Autopay could not pay your fee', end($pushes)[2]['title'] ?? null);
    }

    public function test_stopping_autopay_cancels_it_on_phonepe_and_a_told_debit_is_not_taken(): void
    {
        $this->activeMandate();
        FeeAutopay::run();
        $this->subState = 'CANCELLED';

        $res = $this->api('cancel');
        $this->assertSame(200, $res['status']);
        $this->assertCount(1, $this->calls('/cancel'));
        $this->assertSame('cancelled', FeeAutopayMandate::first()->status);
        $this->assertSame('failed', FeeAutopayDebit::first()->status);
        $this->assertNull($res['data']['mandate']);

        Carbon::setTestNow('2026-11-03 14:00:00');
        FeeAutopay::run();
        $this->assertSame([], $this->calls('/subscriptions/v2/redeem'));

        // The parent revoking it in their UPI app reaches us by webhook.
        $m = $this->activeMandate();
        $this->webhook('subscription.revoked', ['merchantSubscriptionId' => $m->merchant_subscription_id, 'state' => 'REVOKED']);
        $this->assertSame('revoked', $m->fresh()->status);
    }

    public function test_a_setup_never_approved_lapses_in_a_day(): void
    {
        $this->subState = 'ACTIVATION_IN_PROGRESS';
        $this->api('setup');

        Carbon::setTestNow('2026-11-03 11:00:00');
        FeeAutopay::run();

        $m = FeeAutopayMandate::firstOrFail();
        $this->assertSame('failed', $m->status);
        $this->assertSame('Not approved', $m->status_reason);
    }

    public function test_phonepes_debit_hours(): void
    {
        foreach (['13:01' => true, '16:59' => true, '17:00' => false, '21:30' => false, '21:31' => true, '09:59' => true, '10:00' => false, '13:00' => false] as $t => $ok) {
            $this->assertSame($ok, FeeAutopay::inDebitWindow(Carbon::parse('2026-11-03 ' . $t)), $t);
        }
    }

    public function test_the_school_sees_its_autopay_and_can_stop_one(): void
    {
        $m = $this->activeMandate();
        Auth::setUser(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));

        Livewire::test(Fee::class)->assertSee('Autopay');

        $tab = Livewire::test(FeeAutopayTab::class)
            ->assertSee('Aarav Sharma')->assertSee('Active')->assertSee('₹5,000');

        $this->subState = 'CANCELLED';
        $tab->call('confirmStop', $m->id)->call('stop');
        $this->assertSame('cancelled', $m->fresh()->status);
        $this->assertSame('Stopped by the school', $m->fresh()->status_reason);
    }

    public function test_the_return_page_says_whether_autopay_is_on(): void
    {
        $this->api('setup');
        $m = FeeAutopayMandate::firstOrFail();

        $html = app(PhonePeController::class)->autopayReturn($m->setup_order_id)->getContent();
        $this->assertStringContainsString('Autopay is on', $html);
        $this->assertStringContainsString('superlms://payment/return', $html);
        $this->assertSame('active', $m->fresh()->status);
    }
}
