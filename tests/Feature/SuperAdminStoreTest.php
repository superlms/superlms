<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\QuickLinks;
use App\Livewire\SuperAdmin\Store;
use App\Models\Store\StoreInvoice;
use App\Models\Store\StoreProduct;
use App\Models\User;
use App\Services\StoreInvoices;
use App\Support\Platform;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Store (after Messaging): the Store tab's products — images,
 * price, specifications — and the Invoices tab's invoices to schools, with an
 * agreement, a PDF, an email, paid / signed / cancelled.
 */
class SuperAdminStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('s', 32))]);
        config(['services.zeptomail.api_token' => 'test-token', 'services.zeptomail.api_url' => 'https://zepto.test/v1.1']);
        Carbon::setTestNow('2026-11-02 10:00:00');
        Storage::fake('s3');

        $settings = new \ReflectionProperty(Platform::class, 'settings');
        $settings->setValue(null, null);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('mobile_number')->nullable();
            $t->string('address')->nullable(); $t->string('state')->nullable(); $t->string('school_code')->nullable(); $t->string('logo')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->text('permissions')->nullable();
            $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->string('mobile_number')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_11_01_160100_create_platform_settings_table.php'))->up();
        (require base_path('database/migrations/2026_11_02_120000_create_store_tables.php'))->up();

        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Sunrise School', 'email' => 'office@sunrise.test', 'address' => 'Civil Lines, Meerut', 'state' => 'Uttar Pradesh', 'mobile_number' => '9876500001', 'school_code' => 'SUN'],
            ['id' => 2, 'name' => 'Moon School', 'email' => null, 'address' => null, 'state' => null, 'mobile_number' => null, 'school_code' => null],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Super', 'email' => 'super@example.com', 'role' => 'super-admin', 'organization_id' => null, 'allowed_organization_id' => null, 'permissions' => null],
            ['id' => 5, 'name' => 'Asha', 'email' => 'Principal@Sunrise.test', 'role' => 'admin', 'organization_id' => 1, 'allowed_organization_id' => null, 'permissions' => null],
            ['id' => 7, 'name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin', 'organization_id' => null, 'allowed_organization_id' => 2, 'permissions' => json_encode(['super-admin.store'])],
        ]);

        $this->actingAs(User::find(1));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function product(array $over = []): StoreProduct
    {
        return StoreProduct::create($over + [
            'title' => 'Biometric Machine', 'category' => 'Hardware', 'price' => 10000, 'gst_rate' => 18,
            'unit' => 'piece', 'is_active' => true, 'images' => [], 'specifications' => [['label' => 'Warranty', 'value' => '1 year']],
        ]);
    }

    public function test_store_sits_after_messaging_in_the_menu_and_on_quick_links(): void
    {
        $links = array_column(config('menu.super-admin'), 'link');
        $this->assertSame(array_search('super-admin.messaging', $links) + 1, array_search('super-admin.store', $links));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('super-admin.store'));

        $titles = array_column(Livewire::test(QuickLinks::class)->viewData('orderedLinks'), 'title');
        $this->assertContains('Store', $titles);
        $this->assertLessThanOrEqual(30, count($titles));

        // A sub super admin can be given it from the Users screen.
        $this->assertArrayHasKey('super-admin.store', (new \App\Livewire\SuperAdmin\Users())->permissionCatalog());

        Livewire::test(Store::class)
            ->assertSee('Store')->assertSee('Invoices')->assertSee('No products yet')
            ->call('setTab', 'invoices')->assertSee('No invoices yet');
    }

    public function test_a_product_is_added_with_several_images_and_specifications(): void
    {
        $page = Livewire::test(Store::class)
            ->call('openProduct')
            ->set('pTitle', 'Smart Board 75"')
            ->set('pCategory', 'Hardware')
            ->set('pSku', 'SB-75')
            ->set('pPrice', '85000')
            ->set('pMrp', '99000')
            ->set('pGst', '18')
            ->set('pUnit', 'piece')
            ->set('pStock', '4')
            ->set('pDescription', 'A 75 inch interactive board.')
            ->set('pOne', UploadedFile::fake()->image('front.jpg'))
            ->set('pOne', UploadedFile::fake()->image('side.png'))
            ->set('pOne', UploadedFile::fake()->image('back.jpg'))
            ->assertSet('pOne', null)
            ->set('pSpecs.0.label', 'Screen')
            ->set('pSpecs.0.value', '75 inch 4K')
            ->call('addSpec')
            ->set('pSpecs.1.label', 'Warranty')
            ->set('pSpecs.1.value', '3 years')
            ->call('addSpec')   // left empty — dropped
            ->call('saveProduct')
            ->assertHasNoErrors()
            ->assertSet('showProduct', false);

        $p = StoreProduct::firstOrFail();
        $this->assertSame('Smart Board 75"', $p->title);
        $this->assertSame('85000.00', $p->price);
        $this->assertSame('99000.00', $p->mrp);
        $this->assertSame(4, $p->stock);
        $this->assertSame([['label' => 'Screen', 'value' => '75 inch 4K'], ['label' => 'Warranty', 'value' => '3 years']], $p->specifications);
        $this->assertCount(3, $p->images);   // both picks kept
        foreach ($p->images as $url) {
            Storage::disk('s3')->assertExists(StoreInvoices::storagePath($url));
        }

        $page->assertSee('Smart Board 75')->assertSee('₹85,000')->assertSee('14% off')->assertSee('3 photos');

        // Reorder: the third becomes the cover; the second is taken off and leaves storage.
        [$a, $b, $c] = $p->images;
        $page->call('openProduct', $p->id)
            ->call('makeCover', 2)
            ->assertSet('pImages', [$c, $a, $b])
            ->call('removeImage', 2)
            ->call('saveProduct')
            ->assertHasNoErrors();

        $this->assertSame([$c, $a], $p->fresh()->images);
        Storage::disk('s3')->assertMissing(StoreInvoices::storagePath($b));
    }

    public function test_a_product_needs_a_title_and_price_and_at_most_ten_images(): void
    {
        Livewire::test(Store::class)
            ->call('openProduct')
            ->set('pSpecs.0.label', 'Colour')
            ->call('saveProduct')
            ->assertHasErrors(['pTitle', 'pPrice', 'pSpecs.0.value']);

        $page = Livewire::test(Store::class)->call('openProduct');
        foreach (range(1, 11) as $i) {
            $page->set('pOne', UploadedFile::fake()->image("p{$i}.jpg"));
        }
        $page->assertCount('pUploads', 10)->assertHasErrors('pUploads');

        $page->set('pOne', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))->assertHasErrors('pOne');
    }

    public function test_hiding_and_deleting_a_product(): void
    {
        $p = $this->product(['images' => []]);
        Storage::disk('s3')->put('store/products/x.jpg', 'img');
        $p->update(['images' => [Storage::disk('s3')->url('store/products/x.jpg')]]);

        $page = Livewire::test(Store::class)->call('toggleActive', $p->id);
        $this->assertFalse($p->fresh()->is_active);
        $page->assertSee('Hidden');

        // A hidden product is not offered on a new invoice.
        $page->call('setTab', 'invoices')->call('openInvoice')->assertViewHas('pickable', fn ($list) => $list->isEmpty());

        $page->call('confirmDeleteProduct', $p->id)->call('deleteProduct');
        $this->assertNull(StoreProduct::find($p->id));
        Storage::disk('s3')->assertMissing('store/products/x.jpg');
    }

    public function test_an_invoice_is_made_from_the_store_with_gst_and_a_discount(): void
    {
        $machine = $this->product();
        $cards   = $this->product(['title' => 'ID Cards', 'price' => 40, 'gst_rate' => 12, 'unit' => 'card', 'category' => 'Printing']);

        Livewire::test(Store::class)
            ->call('setTab', 'invoices')
            ->call('openInvoice')
            ->assertSet('iDate', '2026-11-02')
            ->assertSet('iDue', '2026-11-17')
            ->set('iOrgId', '1')
            ->set('iPick', (string) $machine->id)
            ->set('iPick', (string) $machine->id)   // picked again → 2 of them
            ->set('iPick', (string) $cards->id)
            ->set('iItems.1.quantity', '500')
            ->call('addLine')
            ->set('iItems.2.title', 'Installation')
            ->set('iItems.2.unit_price', '1500')
            ->set('iItems.2.gst_rate', '18')
            ->set('iDiscount', '2000')
            ->set('iNotes', 'Delivery within 10 days.')
            ->call('saveInvoice')
            ->assertHasNoErrors()
            ->assertSet('showInvoice', false);

        $inv = StoreInvoice::with('items')->firstOrFail();
        $this->assertSame('INV/2026-27/0001', $inv->number);
        $this->assertSame('draft', $inv->status);
        $this->assertSame('Sunrise School', $inv->bill_to['name']);
        $this->assertSame('Civil Lines, Meerut', $inv->bill_to['address']);
        $this->assertCount(3, $inv->items);
        $this->assertSame('2.00', $inv->items[0]->quantity);
        $this->assertSame($machine->id, $inv->items[0]->store_product_id);

        // 20,000 + 20,000 (500 × 40) + 1,500 = 41,500; less 2,000 = 39,500 taxable.
        // GST on each line's share: 18% of 20,000·k + 12% of 20,000·k + 18% of 1,500·k, k = 39,500/41,500.
        $k = 39500 / 41500;
        $tax = round(20000 * $k * 0.18, 2) + round(20000 * $k * 0.12, 2) + round(1500 * $k * 0.18, 2);
        $this->assertSame('41500.00', $inv->subtotal);
        $this->assertSame('2000.00', $inv->discount);
        $this->assertEqualsWithDelta($tax, (float) $inv->tax_total, 0.001);
        $this->assertEqualsWithDelta(39500 + $tax, (float) $inv->total, 0.001);

        // The next one counts on; the financial year starts again in April.
        $this->assertSame('INV/2026-27/0002', StoreInvoices::nextNumber(Carbon::parse('2027-03-31')));
        $this->assertSame('INV/2027-28/0001', StoreInvoices::nextNumber(Carbon::parse('2027-04-01')));

        // A product edited or deleted later leaves the invoice as it was.
        $machine->update(['price' => 1]);
        $machine->delete();
        $line = $inv->fresh('items')->items[0];
        $this->assertSame('Biometric Machine', $line->title);
        $this->assertSame('10000.00', $line->unit_price);
    }

    public function test_an_invoice_needs_a_school_and_named_lines(): void
    {
        Livewire::test(Store::class)
            ->call('openInvoice')
            ->set('iItems.0.unit_price', '100')
            ->set('iDue', '2026-10-01')
            ->call('saveInvoice')
            ->assertHasErrors(['iOrgId', 'iItems.0.title', 'iDue']);

        Livewire::test(Store::class)
            ->call('openInvoice')
            ->set('iOrgId', '1')
            ->set('iItems.0.title', 'Thing')
            ->set('iItems.0.unit_price', '100')
            ->set('iAgreement', true)
            ->set('iAgreementBody', '')
            ->call('saveInvoice')
            ->assertHasErrors('iAgreementBody');
        $this->assertSame(0, StoreInvoice::count());
    }

    public function test_the_agreement_is_filled_in_and_goes_on_the_pdf(): void
    {
        $page = Livewire::test(Store::class)
            ->call('openInvoice')
            ->set('iOrgId', '1')
            ->set('iItems.0.title', 'Annual support')
            ->set('iItems.0.unit_price', '12000')
            ->set('iAgreement', true)
            ->assertSet('iAgreementTitle', 'Purchase Agreement')
            ->assertSet('iAgreementBody', StoreInvoices::SETTINGS['store_agreement_body'])
            ->set('iAgreementFile', UploadedFile::fake()->create('signed-terms.pdf', 40, 'application/pdf'))
            ->call('saveInvoice')
            ->assertHasNoErrors();

        $inv = StoreInvoice::firstOrFail();
        $this->assertTrue($inv->hasAgreement());
        $this->assertSame('signed-terms.pdf', $inv->agreement_file_name);
        Storage::disk('s3')->assertExists(StoreInvoices::storagePath($inv->agreement_file_url));

        $text = StoreInvoices::agreementText($inv);
        $this->assertStringContainsString('SuperLMS ("the Seller") and Sunrise School ("the Buyer")', $text);
        $this->assertStringContainsString('invoice INV/2026-27/0001 dated 02 Nov 2026, for a total of ₹14,160.00', $text);
        $this->assertStringContainsString('by 17 Nov 2026', $text);

        // The open invoice shows it, with its document.
        $page->assertSet('viewId', $inv->id)->assertSee('Purchase Agreement')->assertSee('signed-terms.pdf')->assertSee('Not signed yet');

        [$bytes, $name] = StoreInvoices::pdf($inv);
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertSame('invoice_INV_2026-27_0001.pdf', $name);

        // Signed, then turning the agreement off clears it, its document too.
        $filePath = StoreInvoices::storagePath($inv->agreement_file_url);
        $page->call('toggleSigned', $inv->id);
        $this->assertNotNull($inv->fresh()->agreement_signed_at);
        $page->call('openInvoice', $inv->id)->set('iAgreement', false)->call('saveInvoice')->assertHasNoErrors();
        $inv->refresh();
        $this->assertFalse($inv->hasAgreement());
        $this->assertNull($inv->agreement_signed_at);
        Storage::disk('s3')->assertMissing($filePath);
    }

    public function test_send_emails_the_pdf_to_the_school_and_marks_it_sent(): void
    {
        Http::fake(['zepto.test/*' => Http::response(['request_id' => 'r1'], 200)]);
        StoreInvoices::saveSetting('store_payment_details', 'UPI: superlms@upi');

        $page = Livewire::test(Store::class)
            ->call('openInvoice')
            ->set('iOrgId', '1')
            ->set('iItems.0.title', 'Smart Board')
            ->set('iItems.0.unit_price', '50000')
            ->call('saveInvoice', true)
            ->assertHasNoErrors();

        $inv = StoreInvoice::firstOrFail();
        // The school's email and its admin's are offered.
        $page->assertSet('sendId', $inv->id)->assertSet('sendTo', 'office@sunrise.test, principal@sunrise.test');

        $page->set('sendTo', 'office@sunrise.test, not-an-email')->call('send')->assertHasErrors('sendTo');
        Http::assertNothingSent();

        $page->set('sendTo', 'office@sunrise.test, principal@sunrise.test')
            ->set('sendMessage', 'Please pay by the due date.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sendId', null);

        Http::assertSent(function ($request) use ($inv) {
            $body = $request->data();

            return str_ends_with($request->url(), '/email')
                && $body['subject'] === 'Invoice INV/2026-27/0001 from SuperLMS'
                && array_column(array_column($body['to'], 'email_address'), 'address') === ['office@sunrise.test', 'principal@sunrise.test']
                && count($body['attachments']) === 1
                && $body['attachments'][0]['name'] === 'invoice_INV_2026-27_0001.pdf'
                && str_starts_with(base64_decode($body['attachments'][0]['content']), '%PDF')
                && str_contains($body['htmlbody'], 'Please pay by the due date.')
                && str_contains($body['htmlbody'], 'UPI: superlms@upi')
                && str_contains($body['htmlbody'], 'store/invoice/' . $inv->id . '/pdf');
        });

        $inv->refresh();
        $this->assertSame('sent', $inv->status);
        $this->assertSame('office@sunrise.test, principal@sunrise.test', $inv->sent_to);
        $this->assertNotNull($inv->sent_at);
    }

    public function test_a_failed_email_says_so_and_leaves_the_invoice_a_draft(): void
    {
        Http::fake(['zepto.test/*' => Http::response(['message' => 'Invalid API Token'], 401)]);
        $inv = $this->makeInvoice();

        Livewire::test(Store::class)
            ->call('openSend', $inv->id)
            ->call('send')
            ->assertHasErrors('sendTo');

        $this->assertSame('draft', $inv->fresh()->status);
    }

    public function test_the_schools_link_opens_the_pdf_only_when_signed(): void
    {
        $inv = $this->makeInvoice();

        $this->get(URL::signedRoute('store.invoice.pdf', ['invoice' => $inv->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get('/store/invoice/' . $inv->id . '/pdf')->assertForbidden();

        $inv->update(['status' => 'cancelled']);
        $this->get($inv->publicPdfUrl())->assertStatus(410);
    }

    public function test_paid_unpaid_cancel_and_delete(): void
    {
        $inv = $this->makeInvoice();
        $page = Livewire::test(Store::class)->call('setTab', 'invoices');

        $page->call('openPaid', $inv->id)
            ->set('paidDate', '2026-11-05')->set('paidMode', 'Bank Transfer')->set('paidReference', 'UTR123')
            ->call('markPaid')->assertHasNoErrors();
        $inv->refresh();
        $this->assertSame('paid', $inv->status);
        $this->assertSame('2026-11-05', $inv->paid_at->toDateString());
        $this->assertSame('UTR123', $inv->payment_reference);

        // Paid can't be edited, cancelled or deleted.
        $page->call('openInvoice', $inv->id)->assertSet('showInvoice', false);
        $page->call('confirmCancel', $inv->id)->call('cancelInvoice');
        $page->call('confirmDeleteInvoice', $inv->id)->assertSet('deleteInvoiceId', null);
        $this->assertSame('paid', $inv->fresh()->status);

        $page->call('markUnpaid', $inv->id);
        $this->assertSame('draft', $inv->fresh()->status);
        $this->assertNull($inv->fresh()->paid_at);

        $page->call('confirmCancel', $inv->id)->call('cancelInvoice');
        $this->assertSame('cancelled', $inv->fresh()->status);

        $draft = $this->makeInvoice();
        $page->call('confirmDeleteInvoice', $draft->id)->call('deleteInvoice');
        $this->assertNull(StoreInvoice::find($draft->id));
        $this->assertSame(0, DB::table('store_invoice_items')->where('store_invoice_id', $draft->id)->count());

        $page->assertSee($inv->number)->assertSee('Cancelled');
    }

    public function test_settings_name_the_seller_and_the_number_prefix(): void
    {
        Livewire::test(Store::class)
            ->call('setTab', 'invoices')
            ->call('openSettings')
            ->assertSet('settings.store_seller_name', 'SuperLMS')
            ->set('settings.store_seller_gstin', 'bad')
            ->call('saveSettings')
            ->assertHasErrors('settings.store_seller_gstin')
            ->set('settings.store_seller_gstin', '09abcde1234f1z5')
            ->set('settings.store_seller_address', 'Sector 62, Noida')
            ->set('settings.store_invoice_prefix', 'SLMS')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertSame('09ABCDE1234F1Z5', StoreInvoices::seller()['gstin']);
        $this->assertSame('Sector 62, Noida', StoreInvoices::seller()['address']);
        $this->assertSame('SLMS/2026-27/0001', StoreInvoices::nextNumber(now()));
    }

    public function test_a_sub_super_admin_kept_to_one_school_sees_and_bills_only_it(): void
    {
        $this->makeInvoice(1);
        $this->actingAs(User::find(7));

        $page = Livewire::test(Store::class)->call('setTab', 'invoices');
        $page->assertViewHas('invoices', fn ($p) => $p->total() === 0);
        $page->call('openInvoice')->assertSet('iOrgId', '2');

        $page->set('iOrgId', '1')->set('iItems.0.title', 'X')->set('iItems.0.unit_price', '5')->call('saveInvoice')->assertHasErrors('iOrgId');
        $this->assertSame(1, StoreInvoice::count());
    }

    private function makeInvoice(int $orgId = 1): StoreInvoice
    {
        Livewire::test(Store::class)
            ->call('openInvoice')
            ->set('iOrgId', (string) $orgId)
            ->set('iItems.0.title', 'Support')
            ->set('iItems.0.unit_price', '1000')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        return StoreInvoice::latest('id')->firstOrFail();
    }
}
