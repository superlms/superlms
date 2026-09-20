<?php

namespace Tests\Feature;

use App\Livewire\Admin\PaymentQr;
use App\Livewire\Admin\QrPayments;
use App\Models\Admin\Fee\PaymentQrCode;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fees → Payment QR and QR Payments render, and take what the Fee header
 * sends them: the Add QR button opens the slide-in panel, and the filter bar
 * (status, fee, search, the day a payment was made) reaches the list.
 */
class QrPagesSmokeTest extends TestCase
{
    private int $org = 4;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml leaves APP_KEY out; rendering a Livewire view needs one.
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_qr_codes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('qr_path')->nullable();
            $t->string('upi_id')->nullable();
            $t->string('payee_name')->nullable();
            $t->text('instructions')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('fee_payment_requests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->string('fee_type')->default('academic');
            $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('approved_amount', 12, 2)->nullable();
            $t->string('utr')->nullable();
            $t->date('paid_on')->nullable();
            $t->text('note')->nullable();
            $t->json('meta')->nullable();
            $t->string('status')->default('pending');
            $t->text('review_note')->nullable();
            $t->string('screenshot_path')->nullable();
            $t->unsignedBigInteger('fee_payment_id')->nullable();
            $t->unsignedBigInteger('transport_fee_payment_id')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->string('roll_no')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
        });

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    public function test_payment_qr_is_empty_until_one_is_added_and_the_panel_opens(): void
    {
        Livewire::test(PaymentQr::class)
            ->assertSee('No payment QR yet')
            ->assertSet('showPanel', false)
            // What the Fee header's Add QR button sends.
            ->dispatch('payment-qr-add')
            ->assertSet('showPanel', true)
            ->assertSee('Add payment QR');
    }

    public function test_a_saved_qr_shows_large_with_its_upi_id(): void
    {
        PaymentQrCode::create([
            'organization_id' => $this->org,
            'qr_path'         => 'admin/fees/qr/4/qr.png',
            'upi_id'          => 'school@okaxis',
            'payee_name'      => 'Springfield School',
            'is_active'       => true,
        ]);

        Livewire::test(PaymentQr::class)
            ->assertDontSee('No payment QR yet')
            ->assertSee('Springfield School')
            ->assertSee('school@okaxis')
            ->assertSee('Live in the app')
            ->dispatch('payment-qr-add')
            ->assertSee('Edit payment QR')
            // Removing asks on the panel's own confirm card, not WireUi's dialog.
            ->call('confirmRemove')
            ->assertSet('showDeleteConfirm', true)
            ->assertSee('Remove the payment QR?')
            ->call('cancelRemove')
            ->assertSet('showDeleteConfirm', false);
    }

    public function test_qr_payments_takes_the_header_filters_including_the_day(): void
    {
        Livewire::test(QrPayments::class)
            ->assertSee('Nothing to check')
            ->dispatch('qr-filter', status: 'approved', feeType: 'transport', search: 'raj', date: '2026-09-12')
            ->assertSet('status', 'approved')
            ->assertSet('feeType', 'transport')
            ->assertSet('search', 'raj')
            ->assertSet('date', '2026-09-12')
            ->assertSee('Nothing matches this filter');
    }
}
