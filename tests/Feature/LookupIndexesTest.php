<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The lookup indexes migration: adds organization_id / user_id indexes where a
 * table has the column and no index starting with it, leaves every other table
 * alone, can run twice, keeps the rows, and its down() removes only its own.
 */
class LookupIndexesTest extends TestCase
{
    private function migration(): object
    {
        return require database_path('migrations/2026_10_09_000000_add_lookup_indexes.php');
    }

    private function leading(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->reject(fn ($i) => $i['primary'])
            ->map(fn ($i) => $i['name'] . ':' . implode(',', $i['columns']))
            ->sort()->values()->all();
    }

    public function test_adds_missing_indexes_once_and_down_removes_only_them(): void
    {
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->default(0); $t->unsignedBigInteger('organization_id')->default(0);
        });
        Schema::create('home_works', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->index(['organization_id', 'id'], 'hw_own_index');
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->string('name');                      // no organization_id column
        });
        DB::table('student_details')->insert([['user_id' => 5, 'organization_id' => 2], ['user_id' => 6, 'organization_id' => 2]]);

        $m = $this->migration();
        $m->up();
        $m->up();                                              // a second run adds nothing

        $this->assertSame([
            'student_details_organization_id_lookup_index:organization_id',
            'student_details_user_id_lookup_index:user_id',
        ], $this->leading('student_details'));
        $this->assertSame(['hw_own_index:organization_id,id'], $this->leading('home_works'));
        $this->assertSame([], $this->leading('subjects'));
        $this->assertSame([5, 6], DB::table('student_details')->orderBy('id')->pluck('user_id')->map(fn ($v) => (int) $v)->all());

        $m->down();

        $this->assertSame([], $this->leading('student_details'));
        $this->assertSame(['hw_own_index:organization_id,id'], $this->leading('home_works'));
    }
}
