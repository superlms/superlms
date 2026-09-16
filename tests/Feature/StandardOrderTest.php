<?php

namespace Tests\Feature;

use App\Models\Student\Standard;
use App\Support\StandardOrder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * No two classes of a school share a display order: saving one at a taken
 * order moves the classes in the way down one.
 */
class StandardOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Just the columns these rules touch, on the in-memory test database.
        Schema::create('standards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('board')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function classes(array $orders, int $orgId = 9): array
    {
        $ids = [];
        foreach ($orders as $name => $order) {
            $ids[$name] = Standard::create([
                'organization_id' => $orgId, 'name' => $name, 'order' => $order,
            ])->id;
        }

        return $ids;
    }

    private function orders(int $orgId = 9): array
    {
        return Standard::where('organization_id', $orgId)
            ->orderBy('order')->orderBy('id')
            ->pluck('order', 'name')->all();
    }

    public function test_a_taken_order_moves_the_run_behind_it_down_to_the_first_gap(): void
    {
        $ids = $this->classes([
            'Class 8' => 11, 'Class 10' => 12, 'Class 11' => 13, 'Class 12' => 14,
            'Class 13' => 20, 'Class 9' => 15,
        ]);

        // Class 9 edited to order 12.
        StandardOrder::makeRoom(9, 12, $ids['Class 9']);
        Standard::whereKey($ids['Class 9'])->update(['order' => 12]);

        $this->assertSame([
            'Class 8' => 11, 'Class 9' => 12, 'Class 10' => 13, 'Class 11' => 14,
            'Class 12' => 15, 'Class 13' => 20,
        ], $this->orders());
    }

    public function test_a_free_order_moves_nobody_and_other_schools_are_left_alone(): void
    {
        $ids = $this->classes(['A' => 1, 'B' => 3]);
        $this->classes(['Other' => 2], 10);

        StandardOrder::makeRoom(9, 2, null);
        StandardOrder::makeRoom(9, 3, $ids['B']);

        $this->assertSame(['A' => 1, 'B' => 3], $this->orders());
        $this->assertSame(['Other' => 2], $this->orders(10));
    }

    public function test_a_blank_order_goes_after_the_last_class(): void
    {
        $this->assertSame(1, StandardOrder::next(9));

        $this->classes(['A' => 4, 'B' => 2]);
        $this->assertSame(5, StandardOrder::next(9));
    }

    public function test_leading_zeros_name_the_same_order(): void
    {
        $this->assertSame(12, StandardOrder::parse('012'));
        $this->assertSame(12, StandardOrder::parse(' 12 '));
        $this->assertSame(12, StandardOrder::parse(12));
        $this->assertSame(0, StandardOrder::parse('000'));
        $this->assertNull(StandardOrder::parse(''));
        $this->assertNull(StandardOrder::parse(null));

        foreach (['012', '12', 12, '0', '000012'] as $ok) {
            $this->assertTrue(Validator::make(['o' => $ok], ['o' => ['nullable', StandardOrder::RULE]])->passes(), (string) $ok);
        }
        foreach (['-1', '1.5', 'abc', '1234567'] as $bad) {
            $this->assertFalse(Validator::make(['o' => $bad], ['o' => ['nullable', StandardOrder::RULE]])->passes(), $bad);
        }
    }

    public function test_the_migration_separates_classes_that_share_an_order(): void
    {
        $ids = $this->classes([
            'Nursery' => 0, 'LKG' => 0, 'UKG' => 1,
            'Class 10' => 12, 'Class 11' => 13, 'Class 12' => 14, 'Class 9' => 12,
        ]);
        $this->classes(['Other' => 5], 10);

        // Class 9 was the one last set to 12.
        DB::table('standards')->update(['updated_at' => '2026-09-01 10:00:00']);
        DB::table('standards')->where('id', $ids['Class 9'])->update(['updated_at' => '2026-09-16 10:00:00']);

        (require database_path('migrations/2026_09_16_000002_make_standard_orders_unique.php'))->up();

        $this->assertSame([
            'Nursery' => 0, 'LKG' => 1, 'UKG' => 2,
            'Class 9' => 12, 'Class 10' => 13, 'Class 11' => 14, 'Class 12' => 15,
        ], $this->orders());
        $this->assertSame(['Other' => 5], $this->orders(10));
    }
}
