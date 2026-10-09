<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContactAdmin;
use App\Livewire\Admin\PaymentQr;
use App\Models\Admin\Fee\PaymentQrCode;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Payment QR: no description, note or show/hide switch in its panel — a saved
 * QR is shown in the app — and the QR sits on the left with the UPI ID and its
 * details beside it. Contact Admin lists as Enquiries does. Pages reached from
 * More have no arrow back to it, and the header's Back undoes one click: a tab
 * switch is a history step, a filter or a search is not.
 */
class PanelTidyUpTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);

        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('payment_qr_codes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('qr_path')->nullable(); $t->string('upi_id')->nullable();
            $t->string('payee_name')->nullable(); $t->text('instructions')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('contact_super_admins', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id'); $t->string('topic')->nullable();
            $t->text('admin_query')->nullable(); $t->string('image')->nullable(); $t->text('super_admin_text')->nullable();
            $t->boolean('super_admin_reply')->default(false); $t->string('super_admin_attachment')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Demo School']);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    public function test_payment_qr_panel_has_no_note_or_switch_and_saving_shows_it(): void
    {
        PaymentQrCode::create(['organization_id' => $this->org, 'qr_path' => 'admin/fees/qr/7/qr.png', 'upi_id' => 'school@okaxis',
            'payee_name' => 'Demo School', 'instructions' => 'Old note', 'is_active' => false]);

        $page = Livewire::test(PaymentQr::class);
        $html = $page->html();
        // The QR on the left, a rule, then the UPI ID and the details.
        $this->assertStringContainsString('sm:border-l border-gray-200', $html);
        $this->assertMatchesRegularExpression('/UPI ID<\/span>\s*(<!--\[if BLOCK\]><!\[endif\]-->)?\s*<span class="font-mono text-gray-800 truncate">school@okaxis/', $html);
        $this->assertStringNotContainsString('Old note', $html);

        $html = $page->call('openPanel')->html();
        $this->assertStringNotContainsString('From your bank or UPI app', $html);
        $this->assertStringNotContainsString('Note for students', $html);
        $this->assertStringNotContainsString('Show in the app', $html);

        $page->set('upiId', 'school@okhdfc')->call('save');
        $qr = PaymentQrCode::where('organization_id', $this->org)->first();
        $this->assertTrue((bool) $qr->is_active);                        // shown in the app once saved
        $this->assertNull($qr->instructions);                             // the note is gone
        $this->assertSame('school@okhdfc', $qr->upi_id);
    }

    public function test_contact_admin_lists_as_enquiries(): void
    {
        DB::table('contact_super_admins')->insert([
            ['organization_id' => $this->org, 'user_id' => 1, 'topic' => 'Fees module', 'admin_query' => 'How do I add dues?', 'super_admin_reply' => false, 'image' => 'x.png', 'created_at' => now(), 'updated_at' => now()],
            ['organization_id' => $this->org, 'user_id' => 1, 'topic' => 'Login', 'admin_query' => 'Reset please', 'super_admin_reply' => true, 'image' => null, 'created_at' => now()->subDay(), 'updated_at' => now()],
        ]);

        $html = Livewire::test(ContactAdmin::class)->html();
        $this->assertStringContainsString('>S.No</th>', $html);
        $this->assertStringContainsString('<p class="text-sm font-semibold text-gray-900 truncate">Fees module</p>', $html);
        $this->assertStringContainsString('<span class="text-amber-600">Pending</span>', $html);
        $this->assertStringContainsString('<span class="text-emerald-600">Replied</span>', $html);
        $this->assertSame(1, substr_count($html, 'title="Edit"'));                       // only the one awaiting a reply
        $this->assertStringNotContainsString('rounded-lg border border-gray-200 text-gray-500', $html);   // no boxed buttons
        $this->assertStringNotContainsString('Back to More', $html);
    }

    public function test_back_undoes_one_click_tabs_push_filters_do_not(): void
    {
        // Tabs that were not in the URL before now add a Back step.
        foreach ([
            \App\Livewire\Admin\Homework::class => 'activeTab', \App\Livewire\Admin\SeatingPlan::class => 'activeTab',
            \App\Livewire\Admin\Payroll::class => 'activeTab', \App\Livewire\Admin\Transport::class => 'activeTab',
            \App\Livewire\Admin\Documents::class => 'tab', \App\Livewire\Admin\TcCertificate::class => 'activeTab',
        ] as $class => $prop) {
            $attrs = (new \ReflectionProperty($class, $prop))->getAttributes(Url::class);
            $this->assertCount(1, $attrs, $class);
            $this->assertTrue($attrs[0]->getArguments()['history'] ?? false, $class . ' tab is not a history step');
        }

        // Legacy query strings: the tab still pushes, a filter or a search no longer does.
        $qs = fn ($class) => (fn () => $this->queryString)->call(new $class);
        $fee = $qs(\App\Livewire\Admin\Fee::class);
        $this->assertArrayNotHasKey('history', $fee['activeTab']);                 // default: push
        $this->assertFalse($fee['search']['history']);
        $student = $qs(\App\Livewire\Admin\Student::class);
        foreach (['search', 'filterClass', 'filterSection', 'sortBy'] as $k) {
            $this->assertFalse($student[$k]['history'], "Student {$k}");
        }

        // The arrow back to More renders nothing.
        $this->assertSame('', trim(\Illuminate\Support\Facades\Blade::render('<x-admin.back-to-more />')));
    }
}
