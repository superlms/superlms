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
 * Transport → Drivers: number, photo (a click shows it large), name over the
 * vehicle number, mobile, licence, routes as plain text, and the status as the
 * dot in Actions.
 */
class TransportDriversListTest extends TestCase
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
            $t->boolean('is_active')->default(true);
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

    private function driver(string $name, array $more = [], ?int $org = null): DriverDetail
    {
        $org ??= $this->org;
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@d.test', 'organization_id' => $org]);

        return DriverDetail::create($more + ['user_id' => $user->id, 'organization_id' => $org, 'is_active' => true]);
    }

    private function route(string $name, string $type, int $driver): void
    {
        Transportation::create([
            'organization_id' => $this->org, 'route_name' => $name, 'vehicle_type' => $type,
            'route_group' => 'g-' . $name, 'driver_detail_id' => $driver, 'monthly_fee' => 1000, 'is_active' => true,
        ]);
    }

    /** The drivers table, cut out of the page. */
    private function table(string $html): string
    {
        $from = strpos($html, '<table');

        return substr($html, $from, strpos($html, '</table>') - $from);
    }

    #[DataProvider('pages')]
    public function test_a_driver_row_reads_as_asked(string $pageClass): void
    {
        $ravi = $this->driver('Ravi', [
            'image' => 'https://cdn.test/ravi.jpg', 'phone' => '9876543210', 'license_no' => 'DL-0420',
            'vehicle_no' => 'UP14 AB 1234', 'experience_years' => 7,
        ]);
        $this->route('Sector 12', 'Bus', $ravi->id);
        $this->route('Rampur', 'Van', $ravi->id);

        $table = $this->table(Livewire::test($pageClass)->set('activeTab', 'drivers')->html());
        $gap = '(?:\s|<!--.*?-->)*';

        // Columns: a number first; no Vehicle, Exp or Status of their own.
        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['S.No', 'Driver', 'Mobile', 'License', 'Routes', 'Actions'], array_map('trim', $m[1]));
        $this->assertMatchesRegularExpression('/<td class="px-4 py-3 text-gray-500 font-medium">1<\/td>/', $table);

        // The photo opens large; the vehicle number is under the name.
        $this->assertStringContainsString('wire:click="showDriverPhoto(' . $ravi->id . ')"', $table);
        $this->assertMatchesRegularExpression('/Ravi<\/p>' . $gap . '<p class="text-xs text-gray-400 truncate">UP14 AB 1234<\/p>/s', $table);
        $this->assertStringNotContainsString('ravi@d.test', $table);

        $this->assertStringContainsString('9876543210', $table);
        $this->assertStringContainsString('DL-0420', $table);

        // The routes as plain text, no tags; the experience is not listed.
        $this->assertMatchesRegularExpression('/Sector 12 · Bus, Rampur · Van|Rampur · Van, Sector 12 · Bus/u', $table);
        $this->assertStringNotContainsString('bg-blue-50 text-blue-700', $table);
        $this->assertStringNotContainsString('7y', $table);
    }

    #[DataProvider('pages')]
    public function test_the_numbers_run_on_over_the_pages(string $pageClass): void
    {
        foreach (range(1, 12) as $i) {
            $this->driver('Driver' . $i);
        }

        $page = Livewire::test($pageClass)->set('activeTab', 'drivers')->call('gotoPage', 2);
        $table = $this->table($page->html());

        $this->assertSame(2, substr_count($table, 'wire:key="driver-'));
        $this->assertMatchesRegularExpression('/font-medium">11<\/td>/', $table);
        $this->assertMatchesRegularExpression('/font-medium">12<\/td>/', $table);
    }

    #[DataProvider('pages')]
    public function test_a_click_on_the_photo_shows_it_large(string $pageClass): void
    {
        $ravi = $this->driver('Ravi', ['image' => 'https://cdn.test/ravi.jpg']);
        $plain = $this->driver('Sonu');
        $other = $this->driver('Other', ['image' => 'https://cdn.test/other.jpg'], 99);

        $page = Livewire::test($pageClass)->set('activeTab', 'drivers');
        $this->assertStringNotContainsString('lms-cover', $page->html());
        $this->assertStringNotContainsString('showDriverPhoto(' . $plain->id . ')', $page->html());   // no photo, nothing to open

        $page->call('showDriverPhoto', $ravi->id)->assertSet('driverPhoto', 'https://cdn.test/ravi.jpg');
        $this->assertStringContainsString('lms-cover', $page->html());

        $page->call('closeDriverPhoto')->assertSet('driverPhoto', null);
        $this->assertStringNotContainsString('lms-cover', $page->html());

        // Another school's driver is not shown.
        $page->call('showDriverPhoto', $other->id)->assertSet('driverPhoto', null);
    }

    #[DataProvider('pages')]
    public function test_the_status_is_the_dot_and_still_switches_the_driver(string $pageClass): void
    {
        $ravi = $this->driver('Ravi');

        $page = Livewire::test($pageClass)->set('activeTab', 'drivers');
        $table = $this->table($page->html());
        $this->assertStringContainsString('wire:click="toggleDriverStatus(' . $ravi->id . ')"', $table);
        $this->assertStringContainsString('bg-green-500', $table);
        $this->assertStringNotContainsString('bg-emerald-100', $table);

        $page->call('toggleDriverStatus', $ravi->id);

        $this->assertFalse((bool) $ravi->fresh()->is_active);
        $table = $this->table($page->html());
        $this->assertStringContainsString('bg-red-500', $table);
        $this->assertStringContainsString('title="Inactive"', $table);
    }
}
