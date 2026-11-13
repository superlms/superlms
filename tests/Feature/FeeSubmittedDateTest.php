<?php

namespace Tests\Feature;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use App\Support\FeeSubmittedDate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Schools → Modules → Fees → "Fee submitted date = fee date": a school with it
 * on shows the payment's own date as the receipt's Fee Submitted (at the time
 * it was entered); every other school keeps the day it was entered.
 */
class FeeSubmittedDateTest extends TestCase
{
    private int $org = 9;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('module_organization', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('module_key', 64);
            $t->boolean('enabled')->default(true); $t->timestamps();
            $t->unique(['organization_id', 'module_key']);
        });
    }

    private function payment(string $class = FeePayment::class)
    {
        $p = new $class();
        $p->organization_id = $this->org;
        $p->payment_date    = '2026-08-31';
        $p->created_at      = '2026-10-26 09:01:00';

        return $p;
    }

    public function test_off_by_default_the_entry_date_stays(): void
    {
        $this->assertFalse(FeeSubmittedDate::enabled($this->org));
        $this->assertSame('26 Oct 2026 09:01 AM', FeeSubmittedDate::for($this->payment())->format('d M Y h:i A'));
    }

    public function test_on_the_payment_date_at_the_entry_time(): void
    {
        FeeSubmittedDate::setEnabled($this->org, true);

        $this->assertTrue(FeeSubmittedDate::enabled($this->org));
        $this->assertSame('31 Aug 2026 09:01 AM', FeeSubmittedDate::for($this->payment())->format('d M Y h:i A'));
        $this->assertSame('31 Aug 2026 09:01 AM', FeeSubmittedDate::for($this->payment(TransportFeePayment::class))->format('d M Y h:i A'));

        // Only this school.
        $other = $this->payment();
        $other->organization_id = 10;
        $this->assertSame('26 Oct 2026', FeeSubmittedDate::for($other)->format('d M Y'));
    }

    public function test_switching_off_brings_the_entry_date_back(): void
    {
        FeeSubmittedDate::setEnabled($this->org, true);
        FeeSubmittedDate::setEnabled($this->org, false);

        $this->assertSame('26 Oct 2026', FeeSubmittedDate::for($this->payment())->format('d M Y'));
        $this->assertDatabaseHas('module_organization', [
            'organization_id' => $this->org, 'module_key' => FeeSubmittedDate::KEY, 'enabled' => false,
        ]);
    }

    public function test_it_is_no_module_and_hides_nothing(): void
    {
        FeeSubmittedDate::setEnabled($this->org, false);

        $this->assertArrayNotHasKey(FeeSubmittedDate::KEY, config('modules'));
        $org = new \App\Models\Organization();
        $org->id = $this->org;
        $this->assertTrue($org->hasModule('fees'));
    }

    public function test_the_receipt_row_reads_it(): void
    {
        $off = view('admin.partials.receipt-submitted', ['payment' => $this->payment()])->render();
        $this->assertStringContainsString('26 Oct 2026', $off);

        FeeSubmittedDate::setEnabled($this->org, true);
        $on = view('admin.partials.receipt-submitted', ['payment' => $this->payment()])->render();
        $this->assertStringContainsString('Fee Submitted', $on);
        $this->assertStringContainsString('31 Aug 2026', $on);
        $this->assertStringContainsString('09:01 AM', $on);
        $this->assertStringNotContainsString('26 Oct 2026', $on);
    }
}
