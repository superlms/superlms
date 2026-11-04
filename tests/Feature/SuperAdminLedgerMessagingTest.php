<?php

namespace Tests\Feature;

use App\Livewire\Admin\Messaging as AdminMessaging;
use App\Livewire\SuperAdmin\Ledger;
use App\Livewire\SuperAdmin\Messaging;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Models\Messaging\MessagingWalletTransaction;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\MessagingWallet;
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
 * entries by hand added, edited and deleted) and Messaging (a wallet for
 * each school, filled by the Super Admin or by the school with approval,
 * spent by its WhatsApp and email; the school sends to many from More →
 * Messaging).
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
        Carbon::setTestNow('2026-10-01 00:00:00');   // the wallet counts messages from here
        (require base_path('database/migrations/2026_11_01_140000_create_messaging_tables.php'))->up();
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
        $this->assertStringContainsString('md:grid-cols-6 md:grid-rows-5', $page->html());
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

    public function test_a_wallet_is_filled_by_the_super_admin_or_by_the_school_and_spent_by_its_messages(): void
    {
        $this->actingAs($this->super());
        Livewire::test(Messaging::class)
            ->call('openFunds', 1, 'credit')->set('fundsAmount', '100')->set('fundsMode', 'UPI')->set('fundsReference', 'UTR9')
            ->call('saveFunds')->assertHasNoErrors();
        $this->assertSame(100.0, MessagingWallet::balance(1));

        // Messages since the wallet started cost their rate; a failed one and an older one do not.
        $msg = fn ($status, $at) => WhatsAppMessage::forceCreate(['organization_id' => 1, 'feature' => 'absent_alert', 'template' => 't',
            'status' => $status, 'billable' => true, 'rate' => 0.66, 'created_at' => $at, 'updated_at' => $at]);
        $msg('delivered', '2026-10-20 09:00:00');
        $msg('failed', '2026-10-20 09:05:00');
        $msg('read', '2026-09-20 09:00:00');
        $this->assertSame(99.34, MessagingWallet::balance(1));

        // The school adds funds: pending until approved.
        $this->actingAs(User::find(5));
        Livewire::test(AdminMessaging::class)
            ->call('openFunds')->set('fAmount', '250')->set('fMode', 'UPI')->set('fReference', 'UTR77')->call('requestFunds')->assertHasNoErrors();
        $this->assertSame(99.34, MessagingWallet::balance(1));
        // The Super Admin hears of it.
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', 1)->get()
            ->contains(fn ($n) => str_contains((string) $n->data, 'Approve it in Messaging')));

        $this->actingAs($this->super());
        $page = Livewire::test(Messaging::class)->call('setTab', 'requests');
        $this->assertStringContainsString('Sunrise School · ₹250.00', $this->text($page));
        $page->call('approveRequest', MessagingWalletTransaction::where('status', 'pending')->value('id'));
        $this->assertSame(349.34, MessagingWallet::balance(1));
        // Money the school paid is a credit in the Ledger.
        $this->assertSame(350.0, SuperAdminLedgerService::creditSum(null, null));

        // Taken out, and a rejected request changes nothing.
        Livewire::test(Messaging::class)->call('openFunds', 1, 'debit')->set('fundsAmount', '49.34')->call('saveFunds')->assertHasNoErrors();
        $this->assertSame(300.0, MessagingWallet::balance(1));
        $req = MessagingWallet::request(1, 1000, 'UPI', 'X', null, 5);
        Livewire::test(Messaging::class)->call('openReject', $req->id)->set('rejectReason', 'Not received')->call('confirmReject');
        $this->assertSame('rejected', $req->fresh()->status);
        $this->assertSame(300.0, MessagingWallet::balance(1));

        $wallets = MessagingWallet::all();
        $this->assertSame(300.0, $wallets[1]['balance']);
        $this->assertSame(0.66, $wallets[1]['used']);        // October's message
        $this->assertSame(0.0, $wallets[1]['used_month']);   // none in November yet
        $this->assertSame(0, $wallets[1]['pending']);       // the rejected one is not pending
    }

    public function test_prepaid_stops_a_charged_whatsapp_when_the_wallet_is_short(): void
    {
        config(['services.whatsapp.enabled' => true, 'services.whatsapp.token' => 't', 'services.whatsapp.phone_number_id' => '1']);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        SchoolWhatsApp::setEnabled(1, 'absent_alert', true);
        MessagingWallet::setSetting('prepaid', '1');
        MessagingWallet::adjust(1, 'credit', 0.5, 'Free credit', null, null, 1);

        $this->assertFalse(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k1'));
        $this->assertSame(0, WhatsAppMessage::count());

        MessagingWallet::adjust(1, 'credit', 1, 'UPI', null, null, 1);
        $this->assertTrue(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k2'));
        $this->assertSame(0.84, MessagingWallet::balance(1));

        // Prepaid off: messages always go, the wallet may go below zero.
        MessagingWallet::setSetting('prepaid', '0');
        $this->assertTrue(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k3'));
        $this->assertTrue(SchoolWhatsApp::send(1, 'absent_alert', '9876543210', 'student_absent_alert', ['a', 'b', 'c', 'd'], null, [], 'k4'));
        $this->assertSame(-0.48, MessagingWallet::balance(1));
    }

    public function test_a_school_sends_an_email_and_a_whatsapp_to_a_class_from_its_wallet(): void
    {
        config(['services.zeptomail.api_token' => 'zt', 'services.whatsapp.enabled' => true, 'services.whatsapp.token' => 't', 'services.whatsapp.phone_number_id' => '1']);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.9']], 'request_id' => 'r1'])]);
        MessagingWallet::adjust(1, 'credit', 10, 'UPI', null, null, 1, true);

        $this->actingAs(User::find(5));
        // Nothing is on for the school yet.
        $page = Livewire::test(AdminMessaging::class)->call('setTab', 'send');
        $this->assertStringContainsString('Messaging is not on for your school yet', $this->text($page));

        $this->actingAs($this->super());
        Livewire::test(Messaging::class)->call('openSchool', 1, 'email')->call('toggleEmail')->call('openSchool', 1, 'whatsapp')->call('toggleWhatsApp', 'notice');

        // Email to Class 6 A: Zoya has an email of her own, Ravi his login's.
        $this->actingAs(User::find(5));
        $page = Livewire::test(AdminMessaging::class)->call('setTab', 'send')
            ->set('channel', 'email')->set('audience', 'students')->set('standardId', '7')->set('sectionId', '71')
            ->set('subject', 'Holiday')->set('body', "School is closed on Monday.\nSee you on Tuesday.");
        $this->assertSame(['total' => 2, 'reachable' => 2, 'rate' => 0.1, 'cost' => 0.2, 'label' => 'Class 6 · Section A'], $page->instance()->preview());
        $page->call('review')->assertHasNoErrors()->assertSet('confirming', true)->call('send');

        $campaign = DB::table('messaging_campaigns')->first();
        $this->assertSame('done', $campaign->status);
        $this->assertSame([2, 0, 0], [(int) $campaign->sent, (int) $campaign->failed, (int) $campaign->skipped]);
        $this->assertEqualsCanonicalizing(['ravi@sunrise.test', 'zoya@sunrise.test'], DB::table('email_messages')->pluck('to_email')->all());
        $this->assertSame(9.8, MessagingWallet::balance(1));
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
        $this->assertSame(9.14, MessagingWallet::balance(1));
    }
    public function test_every_tab_of_both_messaging_pages_and_the_ledger_pdf_render(): void
    {
        MessagingWallet::adjust(1, 'credit', 100, 'UPI', 'U1', 'First', 1, true);
        MessagingWallet::request(1, 50, 'UPI', 'U2', null, 5);
        DB::table('messaging_campaigns')->insert([
            'organization_id' => 1, 'channel' => 'email', 'audience' => 'students', 'audience_label' => 'All students',
            'subject' => 'Exam dates', 'body' => 'Exams start on 10 Nov.', 'recipients' => 2, 'done' => 1, 'sent' => 1,
            'rate' => 0.1, 'status' => 'sending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->super());
        $page = Livewire::test(Messaging::class);
        $this->assertStringContainsString('Sunrise School', $this->text($page));
        foreach (['requests' => 'To approve', 'whatsapp' => 'Pick a school', 'sent' => 'Exam dates', 'settings' => 'Where schools pay'] as $tab => $seen) {
            $this->assertStringContainsString($seen, $this->text($page->call('setTab', $tab)), $tab);
        }
        foreach (['whatsapp' => 'Notice', 'sms' => 'SMS is not set up yet', 'email' => 'Email messaging'] as $tab => $seen) {
            $this->assertStringContainsString($seen, $this->text($page->call('openSchool', 1, $tab)), $tab);
        }
        $page->call('openStatement', 1);
        $this->assertStringContainsString('Added by SuperLMS', $this->text($page));
        $this->assertStringContainsString('To approve', $this->text($page));

        $page->call('setTab', 'settings')->set('setWhatsappRate', '0.75')->set('setEmailRate', '0.2')->set('setPrepaid', true)
            ->set('setPaymentDetails', 'UPI: superlms@upi')->call('saveSettings')->assertHasNoErrors();
        $this->assertSame([0.75, 0.2, true], [MessagingWallet::whatsappRate(), MessagingWallet::emailRate(), MessagingWallet::prepaid()]);
        $this->assertSame(0.75, SchoolWhatsApp::rate());

        $this->actingAs(User::find(5));
        $admin = Livewire::test(AdminMessaging::class);
        $this->assertStringContainsString('Wallet balance', $this->text($admin));
        foreach (['send' => 'not on for your school', 'sent' => 'Exam dates', 'usage' => 'WhatsApp'] as $tab => $seen) {
            $this->assertStringContainsString($seen, $this->text($admin->call('setTab', $tab)), $tab);
        }
        $this->assertStringContainsString('UPI: superlms@upi', $this->text($admin->call('openFunds')));

        [$bytes, $name] = SuperAdminLedgerService::statementPdf(null, null, true);
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertStringStartsWith('superlms_ledger_all_', $name);
    }
}
