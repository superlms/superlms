<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Schools;
use App\Models\OrganizationPaymentSetting;
use App\Models\User;
use App\Services\FeeAutopay;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Schools → a school → Online Payment: the school's own PhonePe
 * merchant, and Fee Autopay on it — the tab is on the school view again.
 */
class SuperAdminSchoolOnlinePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('o', 32))]);

        $tracked = new \ReflectionProperty(FeeAutopay::class, 'tracked');
        $tracked->setValue(null, null);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('mobile_number')->nullable();
            $t->string('state')->nullable(); $t->string('address')->nullable(); $t->string('logo')->nullable();
            $t->boolean('status')->default(true); $t->string('school_code')->nullable(); $t->string('affiliation_no')->nullable();
            $t->string('medium')->nullable(); $t->string('education_board')->nullable(); $t->string('udise_number')->nullable();
            $t->string('serial_number')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->text('permissions')->nullable();
            $t->string('mobile_number')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_06_17_000001_create_organization_payment_settings_table.php'))->up();
        (require base_path('database/migrations/2026_11_02_140000_create_fee_autopay_tables.php'))->up();

        DB::table('organizations')->insert(['id' => 1, 'name' => 'Adarsh Satyadev Inter College']);

        $super = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $super->id = 901;
        $super->role = 'super-admin';
        $super->permissions = [];
        $this->actingAs($super);
    }

    public function test_the_online_payment_tab_sets_the_merchant_and_fee_autopay(): void
    {
        $page = Livewire::test(Schools::class)->call('viewSchoolDetail', 1);
        $this->assertStringContainsString("setDetailTab('payment')", $page->html());
        $page->assertSee('Online Payment');

        $page->call('setDetailTab', 'payment')
            ->assertSee('PhonePe Merchant (fee collection)')
            ->call('openPaymentModal')
            ->assertSee('Fee Autopay (UPI AutoPay)');

        // Autopay needs the school's own collection on.
        $page->set('pgClientId', 'M23K3CIXXM08M')
            ->set('pgClientSecret', 'secret')
            ->set('pgAutopay', true)
            ->call('savePaymentSettings')
            ->assertHasErrors('pgAutopay');
        $this->assertNull(OrganizationPaymentSetting::forOrg(1));

        $page->set('pgIsActive', true)->call('savePaymentSettings')->assertHasNoErrors();
        $setting = OrganizationPaymentSetting::forOrg(1);
        $this->assertTrue($setting->autopayReady());

        // The tab says so, and the modal opens with it ticked.
        $page->assertSee('Fee Autopay')->assertSee('On')
            ->call('openPaymentModal')->assertSet('pgAutopay', true)
            ->set('pgAutopay', false)->call('savePaymentSettings');
        $this->assertFalse($setting->fresh()->autopay_enabled);
    }
}
