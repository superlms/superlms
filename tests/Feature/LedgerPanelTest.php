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
