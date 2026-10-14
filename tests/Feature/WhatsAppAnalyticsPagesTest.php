<?php

namespace Tests\Feature;

use App\Livewire\Admin\Analytics;
use App\Livewire\SuperAdmin\Schools;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\SchoolWhatsApp;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Where a school's WhatsApp is seen: Super Admin → Schools → a school → the
 * WhatsApp tab after Modules (the Absent alert / Fee received switches and the
 * analytics), and the school admin's Analytics (the same analytics, to read)
 * — the last 30 days or a month, each kind day by day, what it came to,
 * delivery and the messages, filtered by kind and status.
 */
class WhatsAppAnalyticsPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);
        Carbon::setTestNow('2026-10-15 10:00:00');

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
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps(); });
        (require base_path('database/migrations/2026_10_14_200000_create_whatsapp_messages_tables.php'))->up();

        DB::table('organizations')->insert([['id' => 1, 'name' => 'Sunrise Public School'], ['id' => 2, 'name' => 'Other School']]);
        DB::table('users')->insert([['id' => 10, 'name' => 'Asha', 'organization_id' => 1], ['id' => 11, 'name' => 'Bala', 'organization_id' => 1]]);
        DB::table('student_details')->insert([
            ['id' => 1, 'organization_id' => 1, 'user_id' => 10, 'full_name' => 'Asha Verma'],
            ['id' => 2, 'organization_id' => 1, 'user_id' => 11, 'full_name' => 'Bala Rao'],
        ]);

        $this->message('absent_alert', 'read', '2026-10-15 09:00:00', ['student_detail_id' => 1, 'to_phone' => '919876543210']);
        $this->message('fee_received', 'delivered', '2026-10-15 09:05:00', ['student_detail_id' => 2, 'to_phone' => '919988776655']);
        $this->message('credentials', 'failed', '2026-10-14 12:00:00', ['user_id' => 11, 'to_phone' => '919123456780', 'error' => '131026 Message undeliverable']);
        $this->message('welcome', 'sent', '2026-10-14 13:00:00', ['user_id' => 10, 'to_phone' => '919876543210']);
        $this->message('absent_alert', 'sent', '2026-09-10 08:00:00', ['student_detail_id' => 1]);   // September
        $this->message('absent_alert', 'sent', '2026-10-15 08:00:00', ['organization_id' => 2]);     // another school
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function message(string $feature, string $status, string $at, array $more = []): void
    {
        $charged = SchoolWhatsApp::FEATURES[$feature][1];
        WhatsAppMessage::forceCreate($more + [
            'organization_id' => 1, 'feature' => $feature, 'template' => 't', 'status' => $status,
            'billable' => $charged, 'rate' => $charged ? 0.66 : 0, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    private function text($page): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($page->html()));
    }

    public function test_the_school_view_has_a_whatsapp_tab_after_modules_with_switches_and_analytics(): void
    {
        $super = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $super->id = 901;
        $super->role = 'super-admin';
        $super->permissions = [];
        $this->actingAs($super);

        $page = Livewire::test(Schools::class)->call('viewSchoolDetail', 1);
        $html = $page->html();
        $this->assertTrue(strpos($html, "setDetailTab('modules')") < strpos($html, "setDetailTab('whatsapp')")
            && strpos($html, "setDetailTab('whatsapp')") < strpos($html, "setDetailTab('bank')"), 'WhatsApp comes right after Modules');

        $page->call('setDetailTab', 'whatsapp');
        $html = $page->html();
        $this->assertStringContainsString("wire:click=\"toggleWhatsApp('absent_alert')\"", $html);
        $this->assertStringContainsString("wire:click=\"toggleWhatsApp('fee_received')\"", $html);
        $this->assertStringContainsString('wire:model.live="waPeriod"', $html);
        $this->assertStringContainsString('wire:key="wa-daily-sa-school-1-', $html);
        foreach (['Messages 3 Last 30 days', 'Absent alert 1 Off · ₹0.66 each', 'Fee received 1 Off · ₹0.66 each',
                  'Credentials 0 Send Credentials · ₹0.66 each', 'Welcome (free) 1 On adding someone',
                  'Charged ₹1.32 2 charged · 1 not delivered', 'Read 1 (25%)', 'Delivered 1 (25%)', 'Sent 1 (25%)',
                  'Not delivered 1 (25%)', 'Busiest day 15 Oct · 2', 'Messages · Last 30 days 4',
                  '1 15 Oct 2026 09:05 AM Fee received Bala Rao +91 99887 76655 Delivered ₹0.66',
                  '4 14 Oct 2026 12:00 PM Credentials Bala +91 91234 56780 Not delivered Not charged'] as $seen) {
            $this->assertStringContainsString($seen, $this->text($page));
        }

        // On for this school only, and off again; Credentials has no switch.
        $page->call('toggleWhatsApp', 'absent_alert');
        $this->assertTrue(SchoolWhatsApp::enabled(1, 'absent_alert'));
        $this->assertFalse(SchoolWhatsApp::enabled(2, 'absent_alert'));
        $this->assertStringContainsString('Absent alert 1 On · ₹0.66 each', $this->text($page));
        $page->call('toggleWhatsApp', 'absent_alert')->call('toggleWhatsApp', 'credentials');
        $this->assertFalse(SchoolWhatsApp::enabled(1, 'absent_alert'));
        $this->assertSame(['absent_alert'], DB::table('whatsapp_school_features')->pluck('feature')->all());

        // A month, a kind, a status.
        $this->assertStringContainsString('Messages 1 Sep 2026', $this->text($page->set('waPeriod', '2026-09')));
        $page->set('waPeriod', '')->set('waStatus', 'failed');
        $this->assertStringContainsString('Messages · Last 30 days 1', $this->text($page));
        $this->assertStringContainsString('Credentials Bala', $this->text($page));
        $page->set('waStatus', '')->set('waFeature', 'welcome');
        $this->assertStringContainsString('Messages · Last 30 days 1 ', $this->text($page));

        // Twenty-five at a time.
        for ($i = 0; $i < 30; $i++) {
            $this->message('welcome', 'sent', '2026-10-01 10:00:00');
        }
        $this->assertStringContainsString('Show more (6 more)', $this->text($page->call('$refresh')));
        $this->assertStringNotContainsString('Show more', $this->text($page->call('showMoreWhatsApp')));

        // Another school starts again at the last 30 days, with its own figures.
        $page->set('waPeriod', '2026-09')->call('viewSchoolDetail', 2)->call('setDetailTab', 'whatsapp');
        $this->assertSame(['', '', ''], [$page->get('waPeriod'), $page->get('waFeature'), $page->get('waStatus')]);
        $this->assertStringContainsString('Messages 1 Last 30 days', $this->text($page));
    }

    public function test_the_school_admins_analytics_has_the_whatsapp_section_to_read(): void
    {
        $this->actingAs(User::find(10)->forceFill(['role' => 'admin']));
        SchoolWhatsApp::setEnabled(1, 'fee_received', true);

        $page = Livewire::test(AnalyticsWithoutFigures::class);
        $html = $page->html();
        $this->assertStringContainsString('WhatsApp', $html);
        $this->assertStringNotContainsString('toggleWhatsApp', $html);              // the Super Admin switches them
        $this->assertStringContainsString('wire:key="wa-daily-admin-', $html);
        foreach (['Absent alert: Off · Fee received: On', 'Messages 3 Last 30 days', 'Fee received 1 On · ₹0.66 each',
                  'Charged ₹1.32 2 charged · 1 not delivered', 'Messages a Day', 'Last 30 days · 0.1 a day on average',
                  'Messages · Last 30 days 4', 'Asha Verma'] as $seen) {
            $this->assertStringContainsString($seen, $this->text($page));
        }
        $this->assertStringNotContainsString('Other School', $this->text($page));

        $this->assertStringContainsString('Messages 1 Sep 2026', $this->text($page->set('waPeriod', '2026-09')));
        $this->assertStringContainsString('Sep 2026 · 0.0 a day on average', $this->text($page));
    }
}

/** The admin's Analytics without its other sections' figures (they need the whole school). */
class AnalyticsWithoutFigures extends Analytics
{
    public function mount(): void {}

    protected function loadAll(): void {}
}
