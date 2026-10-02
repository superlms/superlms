<?php

namespace Tests\Feature;

use App\Livewire\Accounts\Transport as AccountsTransportPage;
use App\Livewire\Admin\Transport as AdminTransportPage;
use App\Models\Admin\DriverDetail;
use App\Models\Admin\Transportation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Transport → Routes: the vehicle types under the route's name, the vehicle
 * number under the driver's, a Drop column after Pickup, the 11-month total
 * under the monthly fare, the status as the dot in Actions, 25 routes a page.
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

    #[DataProvider('pages')]
    public function test_a_route_row_reads_as_asked(string $pageClass): void
    {
        $ravi = $this->driver('Ravi', 'UP14 AB 1234');
        $this->route('Sector 12', ['route_group' => 'g1', 'driver_detail_id' => $ravi, 'pickup_time' => '07:30', 'drop_time' => '14:00']);
        $this->route('Sector 12', ['route_group' => 'g1', 'vehicle_type' => 'Van', 'pickup_time' => '07:30', 'drop_time' => '14:00']);

        $table = $this->table(Livewire::test($pageClass)->html());

        // Columns: no Vehicle Type, Vehicle No., Annual, Seats or Status of their own; Drop after Pickup.
        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['Route', 'Driver', 'Pickup', 'Drop', 'Monthly', 'Students', 'Actions'], array_map('trim', $m[1]));

        // Under the names, small and plain. (Livewire leaves its block markers between the lines.)
        $gap = '(?:\s|<!--.*?-->)*';
        $this->assertMatchesRegularExpression('/Sector 12<\/p>' . $gap . '<p class="text-xs text-gray-400">Bus, Van<\/p>/s', $table);
        $this->assertMatchesRegularExpression('/Ravi<\/p>' . $gap . '<p class="text-xs text-gray-400">UP14 AB 1234<\/p>/s', $table);
        $this->assertStringNotContainsString('bg-indigo-50', $table);

        $this->assertStringContainsString('07:30', $table);
        $this->assertStringContainsString('14:00', $table);

        // The fare, and eleven months of it under it.
        $this->assertMatchesRegularExpression('/₹1,200<\/p>' . $gap . '<p class="text-xs text-gray-400">₹13,200 · 11 months<\/p>/su', $table);

        // The seats are not listed.
        $this->assertStringNotContainsString('>40<', $table);
    }

    #[DataProvider('pages')]
    public function test_the_status_is_the_dot_and_still_switches_the_route(string $pageClass): void
    {
        $route = $this->route('Sector 12', ['route_group' => 'g1']);

        $page = Livewire::test($pageClass);
        $table = $this->table($page->html());
        $this->assertStringContainsString("wire:click=\"toggleTransportStatus('g1')\"", $table);
        $this->assertStringContainsString('bg-green-500', $table);
        $this->assertStringNotContainsString('bg-emerald-100', $table);

        $page->call('toggleTransportStatus', 'g1');

        $this->assertFalse((bool) $route->fresh()->is_active);
        $table = $this->table($page->html());
        $this->assertStringContainsString('bg-red-500', $table);
        $this->assertStringContainsString('title="Inactive"', $table);
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
