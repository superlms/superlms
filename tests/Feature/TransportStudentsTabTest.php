<?php

namespace Tests\Feature;

use App\Livewire\Accounts\Transport as AccountsTransportPage;
use App\Livewire\Admin\Transport as AdminTransportPage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Transport → Transport Students: a number first, the year's fee under the
 * monthly fee with its months, View laid out as the Students page's View, and
 * the months editor as a plain grid of the twelve.
 */
class TransportStudentsTabTest extends TestCase
{
    private int $org = 8;

    private int $route = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('driver_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->string('image')->nullable();
            $t->string('phone')->nullable();
            $t->string('license_no')->nullable();
            $t->string('vehicle_no')->nullable();
            $t->integer('experience_years')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('route_name');
            $t->string('vehicle_type')->nullable();
            $t->string('route_group')->nullable();
            $t->unsignedBigInteger('driver_detail_id')->default(0);
            $t->string('pickup_time')->nullable();
            $t->string('drop_time')->nullable();
            $t->decimal('monthly_fee', 10, 2)->default(0);
            $t->unsignedSmallInteger('capacity')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('transportation_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->text('billable_months')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        foreach (['standards', 'sections'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('organization_id')->nullable();
                $t->unsignedBigInteger('standard_id')->nullable();
                $t->string('name');
                $t->timestamps();
            });
        }
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('transportation_id')->nullable();
            $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 10, 2);
            $t->string('payment_mode')->nullable();
            $t->date('payment_date')->nullable();
            $t->string('receipt_number')->nullable();
            $t->string('academic_year')->nullable();
            $t->string('remark')->nullable();
            $t->unsignedBigInteger('submitted_by')->nullable();
            $t->timestamps();
        });

        config(['app.key' => 'base64:' . base64_encode(str_repeat('g', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 500;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);

        DB::table('standards')->insert(['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 5']);
        DB::table('sections')->insert(['id' => 1, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'A']);
        $this->route = DB::table('transportations')->insertGetId([
            'organization_id' => $this->org, 'route_name' => 'Sector 12', 'vehicle_type' => 'Bus', 'route_group' => 'g1',
            'pickup_time' => '07:30', 'drop_time' => '14:00', 'monthly_fee' => 1000, 'is_active' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function pages(): array
    {
        return ['admin' => [AdminTransportPage::class], 'accounts' => [AccountsTransportPage::class]];
    }

    /** A student riding the route; $months = the pivot's billable months, null for the default eleven. */
    private function rider(string $name, ?array $months = null, float $paid = 0): int
    {
        $id = DB::table('student_details')->insertGetId([
            'organization_id' => $this->org, 'full_name' => $name, 'admission_no' => 'A-' . strtoupper(substr($name, 0, 3)),
            'standard_id' => 1, 'section_id' => 1, 'phone' => '9876543210',
        ]);
        DB::table('transportation_students')->insert([
            'organization_id' => $this->org, 'transportation_id' => $this->route, 'student_detail_id' => $id,
            'billable_months' => $months === null ? null : json_encode($months),
        ]);
        if ($paid > 0) {
            DB::table('transport_fee_payments')->insert([
                'organization_id' => $this->org, 'transportation_id' => $this->route, 'student_detail_id' => $id,
                'amount' => $paid, 'payment_date' => '2026-09-01',
            ]);
        }

        return $id;
    }

    private function page(string $pageClass)
    {
        return Livewire::test($pageClass)->set('activeTab', 'students')->set('filterRoute', (string) $this->route);
    }

    /** One slide-in panel, cut out of the page by its heading. */
    private function panel(string $html, string $heading): string
    {
        $at = strpos($html, $heading);
        $this->assertNotFalse($at, "No panel headed '{$heading}'.");
        $from = strrpos(substr($html, 0, $at), '<div class="fixed inset-x-0');

        return substr($html, $from, 9000);
    }

    #[DataProvider('pages')]
    public function test_the_listing_reads_as_asked(string $pageClass): void
    {
        $this->rider('Asha', null, 5400);
        $ten = ['apr' => true, 'may' => false, 'jun' => false, 'jul' => true, 'aug' => true, 'sep' => true, 'oct' => true, 'nov' => true, 'dec' => true, 'jan' => true, 'feb' => true, 'mar' => true];
        $this->rider('Bina', $ten);

        $html = $this->page($pageClass)->html();
        $from = strpos($html, '<table');
        $table = substr($html, $from, strpos($html, '</table>') - $from);
        $gap = '(?:\s|<!--.*?-->)*';

        // A number first; no Months or Annual column.
        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['S.No', 'Student', 'Driver', 'Monthly Fee', 'Paid', 'Remaining', 'Actions'], array_map('trim', $m[1]));
        $this->assertMatchesRegularExpression('/font-medium">1<\/td>.*font-medium">2<\/td>/s', $table);

        // The year's fee under the monthly one, with the months it counts.
        $this->assertMatchesRegularExpression('/₹1,000<\/p>' . $gap . '<p class="text-xs text-gray-400">₹11,000 · 11 months<\/p>/su', $table);
        $this->assertMatchesRegularExpression('/₹1,000<\/p>' . $gap . '<p class="text-xs text-gray-400">₹10,000 · 10 months<\/p>/su', $table);
        $this->assertStringNotContainsString('/12', $table);

        // Paid and remaining as before.
        $this->assertStringContainsString('₹5,400', $table);
        $this->assertStringContainsString('₹5,600', $table);
    }

    #[DataProvider('pages')]
    public function test_view_is_a_plain_list_with_the_months_under_it(string $pageClass): void
    {
        $asha = $this->rider('Asha', null, 4400);   // Apr, May, Jul, Aug paid; Sep 400 of 1,000; Oct unpaid

        $page = $this->page($pageClass)->call('viewTransportStudentDetail', $asha, $this->route);
        $panel = $this->panel($page->html(), 'Transport Details');
        $text = preg_replace('/\s+/', ' ', strip_tags($panel));

        foreach ([
            'Admission No A-ASH', 'Class Class 5-A', 'Mobile 9876543210', 'Route Sector 12',
            'Pickup Time 07:30', 'Drop Time 14:00', 'Monthly Fee ₹1,000', 'Annual Fee ₹11,000 · 11 months',
            'Paid ₹4,400', 'Remaining ₹6,600',
            'April Paid · ₹1,000', 'May Paid · ₹1,000 July Paid · ₹1,000', 'August Paid · ₹1,000',   // no June between May and July
            'September Partial · ₹400 of ₹1,000', 'October Unpaid · ₹1,000', 'November Upcoming · ₹1,000',
        ] as $line) {
            $this->assertStringContainsString($line, $text);
        }

        $this->assertStringNotContainsString('June', $text);

        // No coloured tiles.
        $this->assertStringNotContainsString('bg-emerald-50', $panel);
        $this->assertStringNotContainsString('bg-red-50', $panel);
        $this->assertStringNotContainsString('bg-gray-50 rounded-lg', $panel);
    }

    #[DataProvider('pages')]
    public function test_edit_on_the_view_opens_the_months(string $pageClass): void
    {
        $asha = $this->rider('Asha');

        $this->page($pageClass)
            ->call('viewTransportStudentDetail', $asha, $this->route)
            ->assertSet('viewTxStudentModal', true)
            ->call('editViewedTransportStudent')
            ->assertSet('viewTxStudentModal', false)
            ->assertSet('editTxStudentModal', true)
            ->assertSet('editTxStudentId', $asha)
            ->assertSet('editTxStudentRouteId', $this->route)
            ->assertSet('editTxStudentName', 'Asha');
    }

    #[DataProvider('pages')]
    public function test_the_months_are_a_plain_grid_that_still_saves(string $pageClass): void
    {
        $asha = $this->rider('Asha');

        $page = $this->page($pageClass)->call('editTransportStudent', $asha, $this->route);
        $panel = $this->panel($page->html(), 'Monthly Fee Schedule');
        $text = preg_replace('/\s+/', ' ', strip_tags($panel));

        // Eleven months, lightly tinted when charged; June is not among them.
        $this->assertSame(11, substr_count($panel, 'wire:click="toggleTxMonth('));
        $this->assertStringNotContainsString("toggleTxMonth('jun')", $panel);
        $this->assertSame(11, substr_count($panel, 'bg-blue-50 border-blue-200 text-blue-700'));
        $this->assertStringNotContainsString('bg-gray-900 border-gray-900', $panel);
        $this->assertStringContainsString('Annual Fee ₹11,000 · 11 months', $text);
        $this->assertStringNotContainsString('translate-x-4', $panel);   // no switches

        // June cannot be turned on, whatever asks for it.
        $page->call('toggleTxMonth', 'jun')->assertSet('editTxBillableMonths.jun', false)->call('toggleTxMonth', 'apr');
        $text = preg_replace('/\s+/', ' ', strip_tags($this->panel($page->html(), 'Monthly Fee Schedule')));
        $this->assertStringContainsString('Annual Fee ₹10,000 · 10 months', $text);

        $page->call('saveTransportStudentMonths')->assertSet('editTxStudentModal', false);

        $saved = json_decode(DB::table('transportation_students')->where('student_detail_id', $asha)->value('billable_months'), true);
        $this->assertFalse($saved['apr']);
        $this->assertFalse($saved['jun']);
        $this->assertTrue($saved['may']);
    }

    #[DataProvider('pages')]
    public function test_a_record_with_june_on_loses_it_when_its_months_are_saved(string $pageClass): void
    {
        $all = array_fill_keys(['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'], true);
        $asha = $this->rider('Asha', $all);

        // Until then it is still counted, and still shown, as it is saved.
        $page = $this->page($pageClass)->call('viewTransportStudentDetail', $asha, $this->route);
        $text = preg_replace('/\s+/', ' ', strip_tags($this->panel($page->html(), 'Transport Details')));
        $this->assertStringContainsString('Annual Fee ₹12,000 · 12 months', $text);
        $this->assertStringContainsString('June Paid', str_replace('June Unpaid', 'June Paid', $text));

        $page->call('editViewedTransportStudent')->assertSet('editTxBillableMonths.jun', false);
        $text = preg_replace('/\s+/', ' ', strip_tags($this->panel($page->html(), 'Monthly Fee Schedule')));
        $this->assertStringContainsString('Annual Fee ₹11,000 · 11 months', $text);

        $page->call('saveTransportStudentMonths');
        $saved = json_decode(DB::table('transportation_students')->where('student_detail_id', $asha)->value('billable_months'), true);
        $this->assertFalse($saved['jun']);
        $this->assertTrue($saved['apr']);
    }

    #[DataProvider('pages')]
    public function test_the_fee_summary_is_plain_rows(string $pageClass): void
    {
        $asha = $this->rider('Asha', null, 4400);   // Apr, May, Jul, Aug paid; Sep 400 of 1,000; Oct unpaid

        $html = Livewire::test($pageClass)->set('activeTab', 'fees')
            ->set('feeFilterRoute', (string) $this->route)->set('feeStudentId', $asha)->html();
        $from = strpos($html, 'Monthly Fee Status');
        $about = preg_replace('/\s+/', ' ', strip_tags(substr($html, 0, $from)));
        $months = substr($html, $from, strpos($html, 'Transactions') - $from);
        $text = preg_replace('/\s+/', ' ', strip_tags($months));

        foreach ([
            'Route Sector 12 · Bus', 'Pickup Time 07:30', 'Drop Time 14:00', 'Mobile 9876543210',
            'Monthly Fee ₹1,000', 'Annual Fee ₹11,000 · 11 months', 'Paid ₹4,400 · 40% · 1 receipt', 'Remaining ₹6,600',
        ] as $line) {
            $this->assertStringContainsString($line, $about);
        }

        foreach ([
            'Paid 4 · Partial 1 · Unpaid 1 · Upcoming 5',
            'April 2026 Paid ₹1,000 ₹1,000', 'May 2026 Paid ₹1,000 ₹1,000 July 2026 Paid',   // no June
            'September 2026 Partial ₹400 ₹1,000', 'October 2026 Unpaid ₹0 ₹1,000',
            'November 2026 Upcoming — ₹1,000', 'March 2027 Upcoming — ₹1,000',
        ] as $line) {
            $this->assertStringContainsString($line, $text);
        }
        $this->assertStringNotContainsString('June', $text);
        $this->assertMatchesRegularExpression('/text-red-600 font-medium">Unpaid</', $months);

        // No coloured tiles, no progress bar.
        $start = strrpos(substr($html, 0, $from), 'class="space-y-5"');
        $area = substr($html, $start, strpos($html, 'Transactions') - $start);
        foreach (['bg-emerald-50', 'bg-red-50', 'bg-amber-50', 'bg-emerald-500'] as $class) {
            $this->assertStringNotContainsString($class, $area);
        }
        $this->assertStringContainsString('wire:click="editTransportStudent(' . $asha . ', ' . $this->route . ')"', $months);
    }
}
