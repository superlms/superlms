<?php

namespace Tests\Feature;

use App\Livewire\Admin\Messaging as AdminMessaging;
use App\Livewire\SuperAdmin\Ledger;
use App\Livewire\SuperAdmin\Messaging;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Models\Messaging\MessagingPack;
use App\Models\Messaging\MessagingPayment;
use App\Models\Messaging\MessagingWalletTransaction;
use App\Models\SmsMessage;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\MessagePacks;
use App\Services\MessagingWallet;
use App\Services\SchoolEmail;
use App\Services\SchoolSms;
use App\Services\SchoolWhatsApp;
use App\Services\SuperAdminLedgerService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Ledger (fees received are credits, salaries paid expenses,
 * entries by hand added, edited and deleted) and Messaging (since 21 Nov
 * 2026 message packs: each school has so many WhatsApp, SMS and email
 * messages, given by the Super Admin — paid, to be paid or free — or bought
 * by the school with approval, each message sent taking one; the school
 * sends to many from More → Messaging).
 */
class SuperAdminLedgerMessagingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('m', 32))]);
        Carbon::setTestNow('2026-11-01 10:00:00');

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('logo')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->text('permissions')->nullable();
            $t->string('mobile_number')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('super_admin_fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type')->default('per_student'); $t->timestamps();
        });
        Schema::create('super_admin_employees', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('super_admin_salary_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('super_admin_employee_id'); $t->decimal('amount', 10, 2); $t->string('month')->nullable();
            $t->string('payment_mode')->nullable(); $t->string('paid_by')->nullable(); $t->string('status')->default('pending');
            $t->date('payment_date')->nullable(); $t->string('transaction_id')->nullable(); $t->text('remark')->nullable();
            $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->string('type'); $t->morphs('notifiable'); $t->text('data'); $t->timestamp('read_at')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_12_100000_create_super_admin_fee_receipts_table.php'))->up();
        (require base_path('database/migrations/2026_10_14_200000_create_whatsapp_messages_tables.php'))->up();
        (require base_path('database/migrations/2026_11_01_130000_create_super_admin_ledger_transactions_table.php'))->up();
        (require base_path('database/migrations/2026_10_27_120000_create_sms_tables.php'))->up();
        Carbon::setTestNow('2026-10-01 00:00:00');   // the wallet, then the packs, count messages from here
        (require base_path('database/migrations/2026_11_01_140000_create_messaging_tables.php'))->up();
        (require base_path('database/migrations/2026_11_21_200000_create_messaging_packs_tables.php'))->up();
        Carbon::setTestNow('2026-11-01 10:00:00');

        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public function sendToUser(...$args) {}
        });

        DB::table('organizations')->insert([['id' => 1, 'name' => 'Sunrise School'], ['id' => 2, 'name' => 'Moon School']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Super', 'email' => 'super@example.com', 'role' => 'super-admin', 'organization_id' => null, 'mobile_number' => null],
            ['id' => 5, 'name' => 'Asha Admin', 'email' => 'admin@sunrise.test', 'role' => 'admin', 'organization_id' => 1, 'mobile_number' => null],
            ['id' => 10, 'name' => 'Ravi', 'email' => 'ravi@sunrise.test', 'role' => 'student', 'organization_id' => 1, 'mobile_number' => '9876543210'],
            ['id' => 11, 'name' => 'Zoya', 'email' => null, 'role' => 'student', 'organization_id' => 1, 'mobile_number' => null],
        ]);
        DB::table('standards')->insert(['id' => 7, 'organization_id' => 1, 'name' => 'Class 6', 'order' => 6]);
        DB::table('sections')->insert(['id' => 71, 'organization_id' => 1, 'standard_id' => 7, 'name' => 'A']);
        DB::table('student_details')->insert([
            ['id' => 1, 'organization_id' => 1, 'user_id' => 10, 'standard_id' => 7, 'section_id' => 71, 'full_name' => 'Ravi Kumar', 'email' => null, 'phone' => null],
            ['id' => 2, 'organization_id' => 1, 'user_id' => 11, 'standard_id' => 7, 'section_id' => 71, 'full_name' => 'Zoya Khan', 'email' => 'zoya@sunrise.test', 'phone' => null],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function super(): User
    {
        return User::find(1);
    }

    private function text($page): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($page->html()));
    }

    public function test_the_menu_has_ledger_after_payroll_and_messaging_after_push_notification_on_a_6_by_5_grid(): void
    {
        $links = array_column(config('menu.super-admin'), 'link');
        $this->assertSame(array_search('super-admin.payroll', $links) + 1, array_search('super-admin.ledger', $links));
        $this->assertSame(array_search('super-admin.push-notification', $links) + 1, array_search('super-admin.messaging', $links));

        $this->actingAs($this->super());
        $page = Livewire::test(QuickLinks::class);
        // Six tiles a row on a computer (home-skin's .sk-tiles since 277d3b0).
        $this->assertStringContainsString('.sk-tiles { grid-template-columns: repeat(6, minmax(0, 1fr));', $page->html());
        $titles = array_column($page->viewData('orderedLinks'), 'title');
        $this->assertContains('Ledger', $titles);
        $this->assertContains('Messaging', $titles);
        $this->assertLessThanOrEqual(30, count($titles));
    }

    public function test_the_ledger_reads_fees_as_credits_and_salaries_as_expenses_and_keeps_entries_by_hand(): void
    {
        DB::table('super_admin_fee_receipts')->insert([
            ['organization_id' => 1, 'amount' => 15000, 'academic_year' => '2026-2027', 'payment_mode' => 'online', 'payment_date' => '2026-10-20', 'label' => '214 students', 'items' => 214, 'created_at' => '2026-10-20 10:00:00', 'updated_at' => now()],
            ['organization_id' => 2, 'amount' => -70, 'academic_year' => '2026-2027', 'payment_mode' => 'cash', 'payment_date' => '2026-10-22', 'label' => 'Aarav', 'items' => 1, 'created_at' => '2026-10-22 10:00:00', 'updated_at' => now()],
        ]);
        DB::table('super_admin_employees')->insert(['id' => 1, 'name' => 'Neha']);
        DB::table('super_admin_salary_payments')->insert([
            ['super_admin_employee_id' => 1, 'amount' => 5000, 'month' => '2026-10', 'status' => 'paid', 'payment_mode' => 'bank_transfer', 'payment_date' => '2026-10-31', 'created_at' => now(), 'updated_at' => now()],
            ['super_admin_employee_id' => 1, 'amount' => 3000, 'month' => '2026-11', 'status' => 'pending', 'payment_mode' => null, 'payment_date' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        // A school's paid recharge is a credit; a free credit is not money received.
        MessagingWallet::adjust(1, 'credit', 500, 'UPI', 'UTR1', null, 1, true, '2026-10-25');
        MessagingWallet::adjust(2, 'credit', 100, 'Free credit', null, null, 1, false, '2026-10-25');

        $this->assertSame(15500.0, SuperAdminLedgerService::creditSum(null, null));
        $this->assertSame(5070.0, SuperAdminLedgerService::expenseSum(null, null));
        $this->assertSame(10430.0, SuperAdminLedgerService::netBalance());

        $this->actingAs($this->super());
        $page = Livewire::test(Ledger::class)->call('overall');
        $text = $this->text($page);
        foreach (['Platform fee · 214 students · 2026-2027', 'Sunrise School', 'School Fee', 'Salary · Oct 2026', 'Neha',
                  'Messaging wallet recharge · Ref. UTR1', 'Platform fee corrected · Aarav', 'Credit: ₹15,500.00', 'Expense: ₹5,070.00'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }

        // Add an expense, change it, delete it.
        $page->call('openAdd')->call('chooseType', 'expense')
            ->set('mDate', '2026-11-01')->set('mAmount', '1200')->set('mParty', 'SuperLMS')->set('mPartyTo', 'AWS')
            ->set('mMode', 'Card')->set('mReason', 'Server bill')->call('saveManual')->assertHasNoErrors();
        $id = DB::table('super_admin_ledger_transactions')->value('id');
        $this->assertSame(6270.0, SuperAdminLedgerService::expenseSum(null, null));

        $page->call('openEdit', $id)->set('mAmount', '1500')->set('mReason', 'Server bill (Oct)')->call('saveManual')->assertHasNoErrors();
        $this->assertEquals(1500, DB::table('super_admin_ledger_transactions')->value('amount'));
        $this->assertStringContainsString('Server bill (Oct)', $this->text($page));

        $page->call('deleteManual', $id);
        $this->assertSame(0, DB::table('super_admin_ledger_transactions')->count());

        // A credit needs no "To"; the filter narrows the list.
        $page->call('openAdd')->call('chooseType', 'credit')
            ->set('mAmount', '300')->set('mParty', 'Consulting client')->set('mReason', 'Set-up help')->call('saveManual')->assertHasNoErrors();
        $this->assertSame(15800.0, SuperAdminLedgerService::creditSum(null, null));
        $page->set('sourceFilter', 'Salary');
        $this->assertStringNotContainsString('Set-up help', $this->text($page));
        $this->assertStringContainsString('Salary · Oct 2026', $this->text($page));

        // The analytics sit in the header alone: Credit and Expense open their split
        // by source, a line of it lists just those entries and the same line again
        // shows everything; the old tiles under the header are gone.
        $page->call('clearFilters')->call('overall');
        $text = $this->text($page);
        foreach (['Credit by source', 'Expense by source', 'How the balance closes', 'Opening balance'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }
        $page->call('pickSource', 'expense', 'Salary')->assertSet('typeFilter', 'expense')->assertSet('sourceFilter', 'Salary');
        $this->assertStringNotContainsString('Set-up help', $this->text($page));
        $page->call('pickSource', 'expense', 'Salary')->assertSet('typeFilter', '')->assertSet('sourceFilter', '');
        $this->assertStringContainsString('Set-up help', $this->text($page));
        $page->call('pickSource', 'expense', 'Nope')->assertSet('sourceFilter', '');
    }

    private function wa(string $status, string $at, string $feature = 'absent_alert'): void
    {
        WhatsAppMessage::forceCreate(['organization_id' => 1, 'feature' => $feature, 'template' => 't',
            'status' => $status, 'billable' => true, 'rate' => 0.66, 'created_at' => $at, 'updated_at' => $at]);
    }

    private function bhash(): void
    {
        config([
            'services.bhash_sms.enabled' => true, 'services.bhash_sms.user' => 'u', 'services.bhash_sms.pass' => 'secret', 'services.bhash_sms.sender' => 'SPRLMS',
            'services.bhash_sms.templates.notice' => ['id' => 'T9', 'text' => 'Notice from {#var#}: {#var#} {#var#} {#var#} {#var#} {#var#} - SuperLMS'],
        ]);
    }

    public function test_messages_are_given_or_bought_and_each_message_sent_takes_one(): void
    {
        $this->actingAs($this->super());

        // Given, paid now: the messages are in, the payment kept (a Ledger credit).
        Livewire::test(Messaging::class)
            ->call('openGive', 1, 'whatsapp')->assertSet('gRate', '0.66')->call('pickSize', 1000)
            ->set('gPayment', 'paid')->set('gMode', 'UPI')->set('gReference', 'UTR9')
            ->call('saveGive')->assertHasNoErrors();
        $this->assertSame(['whatsapp' => 1000, 'sms' => 0, 'email' => 0], MessagePacks::left(1));
        $this->assertSame(660.0, (float) MessagingPayment::sum('amount'));
        $this->assertSame(660.0, SuperAdminLedgerService::creditSum(null, null));

        // A sent one takes a message; a failed one and one before packs came do not.
        $this->wa('delivered', '2026-10-20 09:00:00');
        $this->wa('failed', '2026-10-20 09:05:00');
        $this->wa('read', '2026-09-20 09:00:00');
        $this->assertSame(999, MessagePacks::left(1)['whatsapp']);
        // An SMS takes one a part.
        SmsMessage::forceCreate(['organization_id' => 1, 'feature' => 'welcome', 'template' => 'student_welcome', 'status' => 'sent', 'parts' => 2,
            'created_at' => '2026-10-21 09:00:00', 'updated_at' => '2026-10-21 09:00:00']);
        $this->assertSame(-2, MessagePacks::left(1)['sms']);

        // Given on Pay later: the school owes it until a payment is recorded.
        Livewire::test(Messaging::class)
            ->call('openGive', 1, 'sms')->assertSet('gRate', '0.25')->set('gMessages', 500)->set('gPayment', 'due')
            ->call('saveGive')->assertHasNoErrors();
        $this->assertSame(498, MessagePacks::left(1)['sms']);
        $this->assertSame(125.0, MessagePacks::due(1));

        // The school buys email messages: waiting until approved, and the Super Admin hears of it.
        $this->actingAs(User::find(5));
        Livewire::test(AdminMessaging::class)
            ->call('buy', 'email')->assertSet('tab', 'buy')->call('pickSize', 2500)
            ->set('bMode', 'UPI')->set('bReference', 'UTR77')->call('requestBuy')->assertHasNoErrors()->assertSet('tab', 'history');
        $this->assertSame(0, MessagePacks::left(1)['email']);
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', 1)->get()
            ->contains(fn ($n) => str_contains((string) $n->data, 'Approve it in Messaging')));

        $this->actingAs($this->super());
        $page = Livewire::test(Messaging::class)->call('setTab', 'requests');
        $this->assertStringContainsString('Sunrise School · 2,500 Email · ₹250.00', $this->text($page));
        $page->call('approveRequest', MessagingPack::where('status', 'pending')->value('id'));
        $this->assertSame(2500, MessagePacks::left(1)['email']);
        $this->assertSame(910.0, SuperAdminLedgerService::creditSum(null, null));

        // Record Payment starts at what is owed and clears it.
        $page = Livewire::test(Messaging::class)->call('openPay', 1)->assertSet('pAmount', '125');
        $page->set('pReference', 'NEFT1')->call('savePay')->assertHasNoErrors();
        $this->assertSame(0.0, MessagePacks::due(1));
        $this->assertSame(1035.0, SuperAdminLedgerService::creditSum(null, null));
        $this->assertStringContainsString('Messages · Ref. NEFT1', collect(SuperAdminLedgerService::entries(null, null))->pluck('reason')->implode(' | '));

        // Taken back: no more than is left.
        Livewire::test(Messaging::class)->call('openGive', 1, 'whatsapp', 'take')->set('gMessages', 5000)->call('saveGive')->assertHasErrors('gMessages');
        Livewire::test(Messaging::class)->call('openGive', 1, 'whatsapp', 'take')->set('gMessages', 100)->set('gNote', 'Wrong pack')->call('saveGive')->assertHasNoErrors();
        $this->assertSame(899, MessagePacks::left(1)['whatsapp']);

        // A rejected request changes nothing.
        $req = MessagePacks::request(1, 'whatsapp', 1000, 'UPI', 'X', null, 5);
        Livewire::test(Messaging::class)->call('openReject', $req->id)->set('rejectReason', 'Not received')->call('confirmReject');
        $this->assertSame('rejected', $req->fresh()->status);
        $this->assertSame(899, MessagePacks::left(1)['whatsapp']);

        $all = MessagePacks::all();
        $this->assertSame(['whatsapp' => 899, 'sms' => 498, 'email' => 2500], $all[1]['left']);
        $this->assertSame(['whatsapp' => 0, 'sms' => 0, 'email' => 0], $all[1]['month']);   // none in November yet
        $this->assertSame(0, $all[1]['pending']);
        $this->assertSame([1035.0, 1035.0, 0.0], [$all[1]['charged'], $all[1]['paid'], $all[1]['due']]);

        // Each pack's payment, oldest first.
        $state = MessagePacks::payState(MessagingPack::all());
        $this->assertSame(['paid', 'paid', 'paid'], MessagingPack::where('type', 'give')->where('status', 'approved')->orderBy('id')->get()
            ->map(fn ($p) => $state[$p->id]['state'])->all());
    }

    public function test_prepaid_stops_a_message_when_the_school_has_none_left_on_its_channel(): void
    {
        config(['services.whatsapp.enabled' => true, 'services.whatsapp.token' => 't', 'services.whatsapp.phone_number_id' => '1']);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        SchoolWhatsApp::setEnabled(1, 'absent_alert', true);
        MessagingWallet::setSetting('prepaid', '1');

        $this->assertFalse(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k1'));
        $this->assertSame(0, WhatsAppMessage::count());

        MessagePacks::give(1, 'whatsapp', 1, 0.66, null, 'free', null, null, null, 1);
        $this->assertTrue(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k2'));
        $this->assertSame(0, MessagePacks::left(1)['whatsapp']);
        $this->assertFalse(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k3'));
        // Another school's messages are its own.
        MessagePacks::give(2, 'whatsapp', 10, 0.66, null, 'free', null, null, null, 1);
        $this->assertFalse(MessagePacks::canSend(1, 'whatsapp'));
        $this->assertTrue(MessagePacks::canSend(2, 'whatsapp'));

        // A welcome by SMS with no SMS left goes by email and WhatsApp instead.
        $this->bhash();
        SchoolSms::setEnabled(1, 'welcome', true);
        $this->assertFalse(SchoolSms::welcomeOn(1));
        MessagePacks::give(1, 'sms', 10, 0.25, null, 'free', null, null, null, 1);
        $this->assertTrue(SchoolSms::welcomeOn(1));

        // Prepaid off: messages always go and a school may go below zero.
        MessagingWallet::setSetting('prepaid', '0');
        $this->assertTrue(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k4'));
        $this->assertSame(-1, MessagePacks::left(1)['whatsapp']);
    }

    public function test_a_school_sends_an_email_a_whatsapp_and_an_sms_to_a_class(): void
    {
        config(['services.zeptomail.api_token' => 'zt', 'services.whatsapp.enabled' => true, 'services.whatsapp.token' => 't', 'services.whatsapp.phone_number_id' => '1']);
        $this->bhash();
        Http::fake([
            'bhashsms.com/*' => Http::response('S.4455'),
            '*'              => Http::response(['messages' => [['id' => 'wamid.9']], 'request_id' => 'r1']),
        ]);
        foreach (['whatsapp', 'sms', 'email'] as $ch) {
            MessagePacks::give(1, $ch, 10, MessagePacks::rate($ch), null, 'free', null, null, null, 1);
        }

        $this->actingAs(User::find(5));
        // Nothing is on for the school yet.
        $page = Livewire::test(AdminMessaging::class)->call('setTab', 'send');
        $this->assertStringContainsString('Messaging is not on for your school yet', $this->text($page));

        $this->actingAs($this->super());
        Livewire::test(Messaging::class)
            ->call('openSchool', 1, 'email')->call('toggleEmail')
            ->call('openSchool', 1, 'whatsapp')->call('toggleWhatsApp', 'notice')
            ->call('openSchool', 1, 'sms')->call('toggleSms', 'notice');
        $this->assertTrue(SchoolSms::noticeOn(1));
        $this->assertFalse(SchoolSms::noticeOn(2));

        // Email to Class 6 A: Zoya has an email of her own, Ravi his login's.
        $this->actingAs(User::find(5));
        $page = Livewire::test(AdminMessaging::class)->call('setTab', 'send')
            ->set('channel', 'email')->set('audience', 'students')->set('standardId', '7')->set('sectionId', '71')
            ->set('subject', 'Holiday')->set('body', "School is closed on Monday.\nSee you on Tuesday.");
        $this->assertSame(['total' => 2, 'reachable' => 2, 'each' => 1, 'messages' => 2, 'rate' => 0.1, 'cost' => 0.2, 'label' => 'Class 6 · Section A'], $page->instance()->preview());
        $page->call('review')->assertHasNoErrors()->assertSet('confirming', true)->call('send');

        $campaign = DB::table('messaging_campaigns')->first();
        $this->assertSame('done', $campaign->status);
        $this->assertSame([2, 0, 0], [(int) $campaign->sent, (int) $campaign->failed, (int) $campaign->skipped]);
        $this->assertEqualsCanonicalizing(['ravi@sunrise.test', 'zoya@sunrise.test'], DB::table('email_messages')->pluck('to_email')->all());
        $this->assertSame(8, MessagePacks::left(1)['email']);
        $this->assertStringContainsString('Holiday', $this->text($page));   // on the Sent tab

        // WhatsApp to the same class: only Ravi has a number; the lines go as one.
        Livewire::test(AdminMessaging::class)->call('setTab', 'send')
            ->set('channel', 'whatsapp')->set('standardId', '7')->set('body', "Fees due.\nPay by Friday.")
            ->call('review')->assertHasNoErrors()->call('send');
        $wa = DB::table('messaging_campaigns')->latest('id')->first();
        $this->assertSame([1, 0, 1], [(int) $wa->sent, (int) $wa->failed, (int) $wa->skipped]);
        $this->assertSame('notice', WhatsAppMessage::value('feature'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com')
            && str_contains(json_encode($r->data(), JSON_UNESCAPED_UNICODE), 'Fees due. · Pay by Friday.'));
        $this->assertSame(9, MessagePacks::left(1)['whatsapp']);

        // SMS: the DLT notice template, the school's name, then the message in parts.
        $page = Livewire::test(AdminMessaging::class)->call('setTab', 'send')
            ->set('channel', 'sms')->set('standardId', '7')->set('body', "Fees due.\nPay by Friday.");
        $this->assertSame(1, $page->instance()->preview()['each']);
        $page->call('review')->assertHasNoErrors()->call('send');
        $sms = DB::table('messaging_campaigns')->latest('id')->first();
        $this->assertSame(['sms', 1, 0, 1], [$sms->channel, (int) $sms->sent, (int) $sms->failed, (int) $sms->skipped]);
        $row = SmsMessage::first();
        $this->assertSame(['notice', 'campaign:' . $sms->id . ':s1'], [$row->feature, $row->dedupe_key]);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'bhashsms.com')
            && str_contains(urldecode($r->url()), 'Notice from Sunrise School: Fees due. Pay by Friday.')
            && !str_contains(urldecode($r->url()), ' - - '));
        $this->assertSame(9, MessagePacks::left(1)['sms']);

        // Prepaid on with too few left: Review stops it.
        MessagingWallet::setSetting('prepaid', '1');
        MessagePacks::take(1, 'sms', 9, null, 1);
        Livewire::test(AdminMessaging::class)->call('setTab', 'send')
            ->set('channel', 'sms')->set('standardId', '7')->set('body', 'Exam on Monday.')
            ->call('review')->assertHasErrors('body')->assertSet('confirming', false);
    }

    public function test_a_long_sms_notice_is_cut_at_word_ends_into_its_parts(): void
    {
        $this->assertSame(['School closed on Monday for', 'Diwali. Classes resume on', 'Tuesday.'],
            SchoolSms::noticeParts("School closed on Monday for Diwali.\nClasses resume on Tuesday.", 5));
        $this->assertSame(['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'bbbbb c'], SchoolSms::noticeParts(str_repeat('a', 30) . 'bbbbb c', 5));
        $this->assertCount(2, SchoolSms::noticeParts(str_repeat('word ', 40), 2));
    }

    public function test_the_wallet_of_1_november_is_carried_over_into_packs_once(): void
    {
        // As the box was before the packs: no packs, and the wallet in rupees.
        DB::table('messaging_settings')->where('key', 'packs_since')->delete();
        MessagingWallet::adjust(1, 'credit', 100, 'UPI', 'U1', null, 1, true, '2026-10-05');
        $this->wa('delivered', '2026-10-20 09:00:00');
        $this->wa('read', '2026-10-21 09:00:00');                // ₹1.32 used: ₹98.68 left
        DB::table('email_messages')->insert(['organization_id' => 2, 'to_email' => 'a@b.c', 'subject' => 's', 'status' => 'sent',
            'billable' => true, 'rate' => 0.10, 'created_at' => '2026-10-22 09:00:00', 'updated_at' => '2026-10-22 09:00:00']);   // Moon: −₹0.10
        $asked = MessagingWallet::request(1, 66, 'UPI', 'U2', 'Top up', 5, '2026-10-30');

        (require base_path('database/migrations/2026_11_21_200000_create_messaging_packs_tables.php'))->up();
        // Once only.
        (require base_path('database/migrations/2026_11_21_200000_create_messaging_packs_tables.php'))->up();

        $carried = MessagingPack::where('source', 'carried')->orderBy('organization_id')->get();
        $this->assertCount(2, $carried);
        $this->assertSame([1, 'whatsapp', 149, 0.0], [(int) $carried[0]->organization_id, $carried[0]->channel, $carried[0]->messages, (float) $carried[0]->amount]);
        $this->assertSame([2, 0, 0.1], [(int) $carried[1]->organization_id, $carried[1]->messages, (float) $carried[1]->amount]);
        $this->assertSame(0.1, MessagePacks::due(2));

        $moved = MessagingPack::pending()->first();
        $this->assertSame([1, 'whatsapp', 100, 66.0, 'U2'], [(int) $moved->organization_id, $moved->channel, $moved->messages, (float) $moved->amount, $moved->reference]);
        $this->assertSame('moved', $asked->fresh()->status);

        // The messages before are paid for by the wallet: the packs count from now.
        $this->assertSame(149, MessagePacks::left(1)['whatsapp']);
        // The wallet's money stays in the Ledger once.
        $this->assertSame(100.0, SuperAdminLedgerService::creditSum(null, null));
    }

    public function test_every_tab_of_both_messaging_pages_and_the_ledger_pdf_render(): void
    {
        MessagePacks::give(1, 'whatsapp', 1000, 0.66, null, 'paid', 'UPI', 'U1', 'First', 1);
        MessagePacks::give(2, 'sms', 200, 0.25, null, 'due', null, null, null, 1);
        MessagePacks::request(1, 'email', 500, 'UPI', 'U2', null, 5);
        $this->wa('delivered', '2026-11-01 09:00:00');
        DB::table('messaging_campaigns')->insert([
            'organization_id' => 1, 'channel' => 'email', 'audience' => 'students', 'audience_label' => 'All students',
            'subject' => 'Exam dates', 'body' => 'Exams start on 10 Nov.', 'recipients' => 2, 'done' => 1, 'sent' => 1,
            'rate' => 0.1, 'status' => 'sending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->super());
        $page = Livewire::test(Messaging::class);
        $text = $this->text($page);
        foreach (['Messages left', 'Sent this month', 'Received this month', 'To collect', 'Messages by Channel', 'Sent by Month',
                  'Top Schools This Month', 'Running Low', 'Latest Payments', 'Bought by Schools', 'Sunrise School'] as $seen) {
            $this->assertStringContainsString($seen, $text, 'overview: ' . $seen);
        }
        $this->assertStringContainsString('999 left of 1,000 given', $text);

        foreach ([
            'schools'  => ['Sunrise School', 'Moon School', '₹50.00'],
            'requests' => ['To Approve', 'Sunrise School · 500 Email · ₹50.00'],
            'packs'    => ['+1,000', 'To pay', 'Paid'],
            'payments' => ['Who Owes', 'Moon School', 'Received by Month', 'U1'],
            'whatsapp' => ['WhatsApp — Every School', 'Sunrise School'],
            'sms'      => ['SMS — Every School', 'Bhash SMS is not set up'],
            'email'    => ['Email — Every School'],
            'sent'     => ['Exam dates', 'Sent to Many'],
            'settings' => ['Where Schools Pay', 'Prepaid'],
        ] as $tab => $seens) {
            $tabText = $this->text($page->call('setTab', $tab));
            foreach ($seens as $seen) {
                $this->assertStringContainsString($seen, $tabText, $tab . ': ' . $seen);
            }
        }
        $page->call('setTab', 'packs')->set('packState', 'due');
        $this->assertStringContainsString('Moon School', $this->text($page));
        $this->assertStringNotContainsString('+1,000', $this->text($page));

        foreach (['whatsapp' => 'Notice', 'sms' => 'SMS notices', 'email' => 'Email messaging'] as $tab => $seen) {
            $this->assertStringContainsString($seen, $this->text($page->call('openSchool', 1, $tab)), $tab);
        }
        $page->call('openDetail', 1);
        $this->assertStringContainsString('Messages, packs and payments', $this->text($page));
        $page->call('openGive', 1, 'whatsapp');
        $this->assertStringContainsString('Sunrise School gets 1,000 WhatsApp messages — 1,999 left after · ₹660.00 received by UPI.', $this->text($page));
        $page->call('closeGive')->call('openPay', 2);
        $this->assertStringContainsString('Record Payment', $this->text($page));
        $page->assertSet('pAmount', '50');

        $page->call('setTab', 'settings')->set('setWhatsappRate', '0.75')->set('setSmsRate', '0.3')->set('setEmailRate', '0.2')
            ->set('setPrepaid', true)->set('setUpiId', 'bad id')->call('saveSettings')->assertHasErrors('setUpiId');
        $page->set('setUpiId', 'superlms@upi')->set('setPaymentDetails', 'A/c 123')->call('saveSettings')->assertHasNoErrors();
        $this->assertSame([0.75, 0.3, 0.2, true, 'superlms@upi'], [MessagingWallet::whatsappRate(), MessagingWallet::smsRate(), MessagingWallet::emailRate(), MessagingWallet::prepaid(), MessagingWallet::upiId()]);
        $this->assertSame(0.75, SchoolWhatsApp::rate());
        $this->assertStringContainsString('aria-label="UPI QR code"', $page->html());

        $this->actingAs(User::find(5));
        $admin = Livewire::test(AdminMessaging::class);
        $text  = $this->text($admin);
        foreach (['Buy WhatsApp', '999', 'Waiting for approval', 'Latest Packs'] as $seen) {
            $this->assertStringContainsString($seen, $text, 'admin overview: ' . $seen);
        }
        foreach (['send' => 'not on for your school', 'sent' => 'Exam dates', 'usage' => 'WhatsApp', 'history' => 'Waiting for approval'] as $tab => $seen) {
            $this->assertStringContainsString($seen, $this->text($admin->call('setTab', $tab)), $tab);
        }
        $admin->call('buy', 'whatsapp')->call('pickSize', 2500);
        $this->assertStringContainsString('Pay ₹1,875.00', $this->text($admin));
        $this->assertStringContainsString('aria-label="UPI QR code"', $admin->html());
        $this->assertStringContainsString('A/c 123', $this->text($admin));

        [$bytes, $name] = SuperAdminLedgerService::statementPdf(null, null, true);
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertStringStartsWith('superlms_ledger_all_', $name);
    }
}
