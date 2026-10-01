<?php

namespace Tests\Feature;

use App\Livewire\Admin\Ledger;
use App\Models\Admin\LedgerTransaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The panel's Ledger page: a fee row reads as its kind over the admission
 * number, with the student's name over the father's in From; a new entry needs
 * every field; a saved one can only have its date changed; Clear goes back to
 * this month; and Export hands the statement over as a download.
 */
class LedgerPanelTest extends TestCase
{
    private int $org = 7;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Livewire signs its snapshots; the test environment has no key of its own.
        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->string('full_name')->nullable(); $t->string('admission_no')->nullable();
            $t->string('father_name')->nullable(); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->decimal('amount', 10, 2); $t->decimal('penalty_amount', 10, 2)->nullable(); $t->date('payment_date');
            $t->string('fee_type')->nullable(); $t->string('payment_mode')->nullable(); $t->string('receipt_number')->nullable();
            $t->string('submitted_by')->nullable(); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('admin_salary_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('admin_employee_id')->nullable();
            $t->decimal('amount', 10, 2); $t->string('status'); $t->date('payment_date')->nullable();
            $t->string('payment_mode')->nullable(); $t->string('month')->nullable(); $t->integer('year')->nullable();
            $t->string('paid_by')->nullable(); $t->timestamps();
        });
        Schema::create('ledger_transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('type'); $t->decimal('amount', 10, 2);
            $t->date('txn_date'); $t->string('party')->nullable(); $t->string('party_to')->nullable(); $t->string('mode')->nullable();
            $t->text('reason')->nullable(); $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Delhi Public School']);
        $this->admin = User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]);
        $this->actingAs($this->admin);
    }

    public function test_a_fee_row_reads_as_its_kind_and_the_student_with_the_fathers_name(): void
    {
        $sid = DB::table('student_details')->insertGetId([
            'full_name' => 'Aarav Sharma', 'admission_no' => '26DPS150001', 'father_name' => 'Rakesh Sharma',
        ]);
        foreach (['academic' => 'RCPT-1', 'transport' => 'RCPT-2', 'penalty' => 'RCPT-3'] as $type => $receipt) {
            DB::table('fee_payments')->insert(['organization_id' => $this->org, 'student_detail_id' => $sid, 'amount' => 500,
                'payment_date' => now()->toDateString(), 'fee_type' => $type, 'payment_mode' => 'cash', 'receipt_number' => $receipt]);
        }

        $html = Livewire::test(Ledger::class)->html();
        // The table alone: the view panel under it keeps the full particulars.
        $table = substr($html, 0, strpos($html, '</table>'));
        // Without Livewire's block markers, which sit between the lines of a cell.
        $table = preg_replace('/<!--.*?-->/s', '', $table);

        foreach (['Academic', 'Transport', 'Penalty'] as $kind) {
            $this->assertMatchesRegularExpression('/<p class="text-sm font-medium text-gray-900">' . $kind . '<\/p>\s*<p class="text-xs text-gray-400 break-words">26DPS150001<\/p>/', $table);
        }
        $this->assertStringNotContainsString('Academic fee collection', strip_tags($table));
        $this->assertMatchesRegularExpression('/Aarav Sharma\s*<span class="block text-xs text-gray-400">Rakesh Sharma<\/span>/', $table);
        $this->assertStringNotContainsString('Aarav Sharma (26DPS150001)', strip_tags($table));
    }

    public function test_an_admission_fee_and_a_salary_read_as_the_name_over_the_type(): void
    {
        Schema::create('admission_enquiries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('student_name')->nullable();
            $t->decimal('collected_amount', 10, 2)->nullable(); $t->string('payment_mode')->nullable();
            $t->string('collected_by')->nullable(); $t->date('fee_collected_at')->nullable(); $t->timestamps();
        });
        DB::table('admission_enquiries')->insert(['organization_id' => $this->org, 'student_name' => 'Kiran Bedi',
            'collected_amount' => 1500, 'payment_mode' => 'cash', 'fee_collected_at' => now()->toDateString(), 'created_at' => now()]);
        $emp = DB::table('admin_employees')->insertGetId(['name' => 'Meera Nair']);
        DB::table('admin_salary_payments')->insert(['organization_id' => $this->org, 'admin_employee_id' => $emp, 'amount' => 20000,
            'status' => 'paid', 'payment_date' => now()->toDateString(), 'month' => '2026-09', 'created_at' => now()]);

        $html  = Livewire::test(Ledger::class)->html();
        $table = preg_replace('/<!--.*?-->/s', '', substr($html, 0, strpos($html, '</table>')));

        // The name, the type small under it — plain text, no coloured tag, no month.
        $this->assertMatchesRegularExpression('/<p class="text-sm font-medium text-gray-900 break-words">Kiran Bedi<\/p>\s*<p class="text-xs text-gray-400">Admission<\/p>/', $table);
        $this->assertMatchesRegularExpression('/<p class="text-sm font-medium text-gray-900 break-words">Meera Nair<\/p>\s*<p class="text-xs text-gray-400">Salary<\/p>/', $table);
        $this->assertStringNotContainsString('Admission fee collection', strip_tags($table));
        $this->assertStringNotContainsString('2026-09', strip_tags($table));
        $this->assertStringNotContainsString('bg-teal-50', $table);
        $this->assertStringNotContainsString('bg-orange-50', $table);

        // View still carries the whole line, month included (its dot is escaped in the page's script).
        $this->assertMatchesRegularExpression("/reason: 'Salary .{1,8} 2026-09'/", $html);
    }

    public function test_the_export_popup_opens_on_all_time_with_download_on_the_right(): void
    {
        $html = Livewire::test(Ledger::class)->call('overall')->html();

        $this->assertStringContainsString("expMode: 'all'", $html);

        // All time, then Single day, then Date range.
        $popup = substr($html, strpos($html, 'Export Statement'));
        $this->assertTrue(strpos($popup, 'All time') < strpos($popup, 'Single day'));
        $this->assertTrue(strpos($popup, 'Single day') < strpos($popup, 'Date range'));

        // Cancel first, Download PDF after it (the right-hand side).
        $this->assertTrue(strpos($popup, 'Cancel') < strpos($popup, 'Download PDF'));

        // Clear sits straight after the End date, not pushed to the far side.
        $this->assertMatchesRegularExpression('/wire:click="clearFilters"\s*class="inline-flex/', $html);
    }

    public function test_the_list_is_fifty_a_page_with_auto_and_manual_as_plain_text(): void
    {
        for ($i = 0; $i < 60; $i++) {
            DB::table('fee_payments')->insert(['organization_id' => $this->org, 'amount' => 100 + $i,
                'payment_date' => now()->toDateString(), 'fee_type' => 'academic', 'payment_mode' => 'cash']);
        }
        LedgerTransaction::create(['organization_id' => $this->org, 'type' => 'expense', 'amount' => 10,
            'txn_date' => now()->toDateString(), 'party' => 'Cash box', 'party_to' => 'Shop', 'mode' => 'Cash', 'reason' => 'Chalk']);

        $page = Livewire::test(Ledger::class)
            ->assertViewHas('entries', fn ($rows) => $rows->perPage() === 50 && $rows->count() === 50 && $rows->total() === 61);

        // Auto / Manual under the mode: small grey text, no coloured tag.
        $html = $page->html();
        $this->assertStringContainsString('<span class="block text-[11px] text-gray-400" title="Added by hand in the ledger">Manual</span>', $html);
        $this->assertStringContainsString('<span class="block text-[11px] text-gray-400" title="Recorded automatically — view only">Auto</span>', $html);
        $this->assertStringNotContainsString('bg-purple-50 text-purple-600', $html);

        // "What are you adding?": the two kinds by name alone.
        $page->call('openAdd')
            ->assertSee('What are you adding?')
            ->assertDontSee('Money coming in')
            ->assertDontSee('Money going out');
    }

    public function test_a_new_entry_needs_every_field(): void
    {
        Livewire::test(Ledger::class)
            ->call('openAdd')->call('chooseType', 'credit')
            ->set('mAmount', '100')->set('mMode', '')
            ->call('saveManual')
            ->assertHasErrors(['mParty', 'mCollectedBy', 'mMode', 'mReason'])
            ->assertHasNoErrors(['mPartyTo']);
        $this->assertSame(0, LedgerTransaction::count());

        Livewire::test(Ledger::class)
            ->call('openAdd')->call('chooseType', 'expense')
            ->set('mAmount', '100')->set('mParty', 'Cash box')->set('mReason', 'Chalk')
            ->call('saveManual')
            ->assertHasErrors(['mPartyTo'])
            ->assertHasNoErrors(['mCollectedBy', 'mParty', 'mMode']);
        $this->assertSame(0, LedgerTransaction::count());

        Livewire::test(Ledger::class)
            ->call('openAdd')->call('chooseType', 'expense')
            ->set('mAmount', '100')->set('mParty', 'Cash box')->set('mPartyTo', 'Stationers')->set('mReason', 'Chalk')
            ->call('saveManual')
            ->assertHasNoErrors();
        $this->assertSame(['expense', 'Cash box', 'Stationers', 'Cash', 'Chalk'],
            array_values(LedgerTransaction::first()->only(['type', 'party', 'party_to', 'mode', 'reason'])));
    }

    public function test_a_saved_entry_can_only_have_its_date_changed(): void
    {
        // An older entry, saved when From and Collected by were optional.
        $txn = LedgerTransaction::create([
            'organization_id' => $this->org, 'type' => 'credit', 'amount' => 100,
            'txn_date' => now()->subDay()->toDateString(), 'reason' => 'Donation', 'mode' => 'Cash',
        ]);
        $newDate = now()->subDays(3)->toDateString();

        Livewire::test(Ledger::class)
            ->call('openEdit', $txn->id)
            ->assertSee('Only its date can be changed')
            ->set('mDate', $newDate)
            // None of these may reach the row.
            ->set('mAmount', '999')->set('mParty', 'Someone')->set('mReason', 'Changed')->set('mMode', 'UPI')
            ->call('saveManual')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $txn->refresh();
        $this->assertSame($newDate, $txn->txn_date->toDateString());
        $this->assertSame(100.0, (float) $txn->amount);
        $this->assertNull($txn->party);
        $this->assertSame('Donation', $txn->reason);
        $this->assertSame('Cash', $txn->mode);

        // Past the correction window even the date is closed.
        $txn->forceFill(['created_at' => now()->subDays(8)])->save();
        Livewire::test(Ledger::class)->call('openEdit', $txn->id)->assertSet('showModal', false);
    }

    public function test_clear_shows_once_the_window_is_changed_and_goes_back_to_this_month(): void
    {
        $page = Livewire::test(Ledger::class)->assertDontSeeHtml('wire:click="clearFilters"');

        $page->call('overall')
            ->assertSeeHtml('wire:click="clearFilters"')
            ->call('clearFilters')
            ->assertSet('startDate', now()->startOfMonth()->toDateString())
            ->assertSet('endDate', now()->toDateString())
            ->assertDontSeeHtml('wire:click="clearFilters"');
    }

    public function test_export_hands_the_statement_over_as_a_download(): void
    {
        DB::table('fee_payments')->insert(['organization_id' => $this->org, 'amount' => 500,
            'payment_date' => '2026-08-05', 'fee_type' => 'academic', 'payment_mode' => 'cash']);

        Livewire::test(Ledger::class)
            ->call('exportStatement', 'range', '2026-08-01', '2026-08-31', '')
            ->assertFileDownloaded('ledger_statement_20260801_20260831.pdf');

        Livewire::test(Ledger::class)
            ->call('exportStatement', 'all', '', '', '')
            ->assertFileDownloaded('ledger_statement_all_' . now()->format('Ymd') . '.pdf');

        // No period, no file.
        Livewire::test(Ledger::class)
            ->call('exportStatement', 'range', '', '', '')
            ->assertNoFileDownloaded();
    }
}
