<?php

namespace Tests\Feature;

use App\Livewire\Accounts\Transport as AccountsTransportPage;
use App\Livewire\Admin\Transport as AdminTransportPage;
use App\Models\Admin\DriverDetail;
use App\Models\Admin\Transportation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Transport → Routes: a row for every vehicle of a route, each on its own — the
 * vehicle type under the route's name, the vehicle number under the driver's, a
 * Drop column after Pickup, the 11-month total under the monthly fare, the
 * status as the dot in Actions, 25 routes a page — and the filter bar's Clear.
 */
class TransportRoutesListTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();

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
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('full_name')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        config(['app.key' => 'base64:' . base64_encode(str_repeat('g', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 500;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);
    }

    public static function pages(): array
    {
        return ['admin' => [AdminTransportPage::class], 'accounts' => [AccountsTransportPage::class]];
    }

    private function driver(string $name, string $vehicleNo): int
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@d.test', 'organization_id' => $this->org]);

        return DriverDetail::create(['user_id' => $user->id, 'organization_id' => $this->org, 'vehicle_no' => $vehicleNo, 'is_active' => true])->id;
    }

    private function route(string $name, array $more = []): Transportation
    {
        return Transportation::create($more + [
            'organization_id' => $this->org, 'route_name' => $name, 'vehicle_type' => 'Bus',
            'monthly_fee' => 1200, 'capacity' => 40, 'is_active' => true,
        ]);
    }

    /** The routes table, cut out of the page. */
    private function table(string $html): string
    {
        $from = strpos($html, '<table');

        return substr($html, $from, strpos($html, '</table>') - $from);
    }

    /** One route's row, cut out of the table, as plain text. */
    private function rowText(string $table, int $id): string
    {
        $from = strpos($table, 'wire:key="route-' . $id . '"');
        $this->assertNotFalse($from, "No row for route {$id}.");
        $row = substr($table, $from);
        $row = substr($row, strpos($row, '>') + 1, strpos($row, '</tr>') - strpos($row, '>') - 1);

        return trim(preg_replace('/\s+/', ' ', strip_tags($row)));
    }

    /** Puts $count students on a route. */
    private function riders(Transportation $route, int $count): void
    {
        foreach (range(1, $count) as $i) {
            $student = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'Rider ' . $route->id . '-' . $i]);
            DB::table('transportation_students')->insert(['organization_id' => $this->org, 'transportation_id' => $route->id, 'student_detail_id' => $student]);
        }
    }

    /** Sector 12, run by a Bus (Ravi, two riders) and a Van (Sonu, one rider). */
    private function busAndVan(): array
    {
        $ravi = $this->driver('Ravi', 'UP14 AB 1234');
        $sonu = $this->driver('Sonu', 'UP14 CD 5678');
        DriverDetail::whereKey($ravi)->update(['image' => 'https://cdn.test/ravi.jpg']);

        $bus = $this->route('Sector 12', ['route_group' => 'g1', 'driver_detail_id' => $ravi, 'pickup_time' => '07:30', 'drop_time' => '14:00']);
        $van = $this->route('Sector 12', ['route_group' => 'g1', 'vehicle_type' => 'Van', 'driver_detail_id' => $sonu, 'pickup_time' => '07:30', 'drop_time' => '14:00']);
        $this->riders($bus, 2);
        $this->riders($van, 1);

        return [$bus, $van, $ravi];
    }

    #[DataProvider('pages')]
    public function test_each_vehicle_of_a_route_is_a_row_of_its_own(string $pageClass): void
    {
        [$bus, $van, $ravi] = $this->busAndVan();

        $html = Livewire::test($pageClass)->html();
        $table = $this->table($html);

        // Columns: no Vehicle Type, Vehicle No., Annual, Seats or Status of their own; Drop after Pickup.
        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['Route', 'Driver', 'Pickup', 'Drop', 'Monthly', 'Students', 'Actions'], array_map('trim', $m[1]));

        // Two rows for the one route, each with its own vehicle, driver and riders.
        $this->assertSame(2, substr_count($table, 'wire:key="route-'));
        $this->assertSame('Sector 12 Bus Ravi UP14 AB 1234 07:30 14:00 ₹1,200 ₹13,200 · 11 months 2', $this->rowText($table, $bus->id));
        $this->assertSame('Sector 12 Van S Sonu UP14 CD 5678 07:30 14:00 ₹1,200 ₹13,200 · 11 months 1', $this->rowText($table, $van->id));
        $this->assertStringNotContainsString('Bus, Van', $table);
        $this->assertStringNotContainsString('bg-indigo-50', $table);

        // The driver's photo opens large; the header counts them as two routes.
        $this->assertStringContainsString('wire:click="showDriverPhoto(' . $ravi . ')"', $table);
        $this->assertMatchesRegularExpression('/Routes: <strong class="text-blue-600">2<\/strong>/', $html);
    }

    #[DataProvider('pages')]
    public function test_a_row_is_switched_viewed_edited_and_deleted_by_itself(string $pageClass): void
    {
        [$bus, $van] = $this->busAndVan();
        $other = Transportation::create(['organization_id' => 99, 'route_name' => 'Elsewhere', 'vehicle_type' => 'Bus', 'monthly_fee' => 700, 'is_active' => true]);

        $page = Livewire::test($pageClass);

        // The status dot switches the one row.
        $table = $this->table($page->html());
        $this->assertStringContainsString('wire:click="toggleRouteRowStatus(' . $van->id . ')"', $table);
        $this->assertStringNotContainsString('bg-emerald-100', $table);
        $page->call('toggleRouteRowStatus', $van->id)->call('toggleRouteRowStatus', $other->id);
        $this->assertFalse((bool) $van->fresh()->is_active);
        $this->assertTrue((bool) $bus->fresh()->is_active);
        $this->assertTrue((bool) $other->fresh()->is_active);   // another school's route is left alone
        $table = $this->table($page->html());
        $this->assertSame(1, substr_count($table, 'bg-green-500'));
        $this->assertSame(1, substr_count($table, 'bg-red-500'));

        // View shows that vehicle's route.
        $details = $page->call('viewRouteRow', $van->id)->assertSet('routeViewTitle', 'Sector 12')->get('routeViewDetails');
        $this->assertSame(['Van', 'Sonu', 'UP14 CD 5678', 1, 'Inactive'], [$details['Vehicle Type'], $details['Driver'], $details['Vehicle No.'], $details['Students'], $details['Status']]);

        // Edit changes that row alone, and its vehicle type is the one picked.
        $page->call('closeRouteView')->call('editRouteRow', $van->id)
            ->assertSet('route_vehicle_type', 'Van')->assertSet('route_name', 'Sector 12')
            ->set('monthly_fee', 900)->set('route_vehicle_type', 'Auto')->set('transport_is_active', true)
            ->call('saveTransport')
            ->assertSet('transportModal', false)->assertSet('editRouteRowId', null);
        $this->assertSame(['Auto', '900.00', true], [$van->fresh()->vehicle_type, $van->fresh()->monthly_fee, (bool) $van->fresh()->is_active]);
        $this->assertSame(['Bus', '1200.00'], [$bus->fresh()->vehicle_type, $bus->fresh()->monthly_fee]);
        $this->assertSame(2, Transportation::where('organization_id', $this->org)->count());

        // Delete removes that row and unassigns its riders; the Bus stays.
        $page->call('confirmDeleteRouteRow', $van->id)->assertSet('pendingDeleteRouteRowId', $van->id)->call('executeDeleteRouteRow');
        $this->assertNull(Transportation::find($van->id));
        $this->assertNotNull(Transportation::find($bus->id));
        $this->assertSame(0, DB::table('transportation_students')->where('transportation_id', $van->id)->count());
        $this->assertSame(2, DB::table('transportation_students')->where('transportation_id', $bus->id)->count());
    }

    #[DataProvider('pages')]
    public function test_add_route_with_several_vehicle_types_adds_a_route_for_each(string $pageClass): void
    {
        $page = Livewire::test($pageClass)->call('createTransport')
            ->set('route_name', 'Rampur')->set('route_vehicle_types', ['Bus', 'Van'])->set('monthly_fee', 500)
            ->call('saveTransport')->assertSet('transportModal', false);

        $this->assertSame(['Bus', 'Van'], Transportation::where('route_name', 'Rampur')->orderBy('id')->pluck('vehicle_type')->all());
        $this->assertSame(2, substr_count($this->table($page->html()), 'wire:key="route-'));
    }

    #[DataProvider('pages')]
    public function test_clear_empties_every_filter(string $pageClass): void
    {
        $bus = $this->route('Sector 12', ['route_group' => 'g1']);

        $page = Livewire::test($pageClass)
            ->set('search', 'sec')->set('filterDriver', '5')->set('filterStatus', '1')->set('filterRoute', (string) $bus->id)
            ->set('feeFilterRoute', (string) $bus->id)->set('feeStudentId', 7);

        // The button calls the one method; it no longer strings $set calls together.
        $this->assertStringContainsString('wire:click="clearFilters"', $page->html());
        $this->assertStringNotContainsString('$set(\'filterDriver\'', $page->html());

        $page->call('clearFilters')
            ->assertSet('search', '')->assertSet('filterDriver', '')->assertSet('filterStatus', '')->assertSet('filterRoute', '')
            ->assertSet('feeFilterRoute', '')->assertSet('feeStudentId', null);
    }

    #[DataProvider('pages')]
    public function test_twenty_five_routes_a_page_and_the_other_tabs_as_before(string $pageClass): void
    {
        foreach (range(1, 30) as $i) {
            $this->route('Route ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }
        foreach (range(1, 12) as $i) {
            $this->driver('Driver' . $i, 'V' . $i);
        }

        $page = Livewire::test($pageClass);
        $this->assertSame(25, substr_count($page->html(), 'wire:key="route-'));

        $page->call('gotoPage', 2);
        $this->assertSame(5, substr_count($page->html(), 'wire:key="route-'));

        $page->set('activeTab', 'drivers');
        $this->assertSame(10, substr_count($page->html(), 'wire:key="driver-'));
    }
}
