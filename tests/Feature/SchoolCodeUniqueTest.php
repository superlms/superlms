<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Schools;
use App\Models\Organization;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Super Admin → Schools → Add School: every school has a code of its own. The
 * same code is refused as before, and now also in other capitals, with spaces
 * around it, or matching an older school's code saved with spaces; a school
 * being edited keeps its own code. Two saves at one moment are stopped by the
 * database's index and told under the code.
 */
class SchoolCodeUniqueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('state')->nullable();
            $t->string('education_board')->nullable();
            $t->string('medium')->nullable();
            $t->string('school_code')->nullable();
            $t->string('serial_number')->nullable();
            $t->text('address')->nullable();
            $t->string('logo')->nullable();
            $t->boolean('status')->default(true);
            foreach (['affiliation_no', 'udise_number', 'bank_name', 'bank_account_no', 'bank_ifsc', 'bank_branch', 'bank_holder_name'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
        });

        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Delhi Public School', 'email' => 'dps@test.in', 'school_code' => 'DPS', 'serial_number' => '1'],
            ['id' => 2, 'name' => 'Old School', 'email' => 'old@test.in', 'school_code' => ' ABC ', 'serial_number' => '2'],
        ]);
    }

    /** The Add School form filled in, with this code. */
    private function form(string $code, ?int $editId = null): Schools
    {
        $c = new Schools();
        $c->schoolName     = 'New School';
        $c->email          = 'new@test.in';
        $c->mobileNumber   = '9999999999';
        $c->state          = 'Uttar Pradesh';
        $c->educationBoard = 'CBSE';
        $c->schoolCode     = $code;
        $c->serialNumber   = 'S-9';
        if ($editId) {
            $c->editId       = $editId;
            $c->email        = DB::table('organizations')->where('id', $editId)->value('email');
            $c->serialNumber = DB::table('organizations')->where('id', $editId)->value('serial_number');
        }

        return $c;
    }

    private function refused(string $code, ?int $editId = null): bool
    {
        try {
            $this->form($code, $editId)->goToModuleStep();
        } catch (ValidationException $e) {
            return isset($e->errors()['schoolCode']);
        }

        return false;
    }

    public function test_a_code_another_school_has_is_refused_as_before(): void
    {
        $this->assertTrue($this->refused('DPS'));
    }

    public function test_the_same_code_in_other_capitals_or_with_spaces_is_refused(): void
    {
        $this->assertTrue($this->refused('dps'));
        $this->assertTrue($this->refused(' DPS '));
        $this->assertTrue($this->refused('Dps  '));
    }

    public function test_an_older_school_s_code_saved_with_spaces_is_still_its_own(): void
    {
        $this->assertTrue($this->refused('ABC'));
        $this->assertTrue($this->refused('abc'));
    }

    public function test_a_new_code_goes_on_without_the_spaces_around_it(): void
    {
        $c = $this->form('  KVS ');
        $c->goToModuleStep();

        $this->assertSame(2, $c->modalStep);
        $this->assertSame('KVS', $c->schoolCode);
    }

    public function test_a_school_being_edited_keeps_its_own_code(): void
    {
        $this->assertFalse($this->refused('DPS', 1));
        $this->assertFalse($this->refused('dps', 1));
        // …but cannot take another school's.
        $this->assertTrue($this->refused('ABC', 1));
    }

    public function test_two_saves_at_one_moment_the_database_stops_the_second(): void
    {
        Schema::table('organizations', fn (Blueprint $t) => $t->unique('school_code', 'organizations_school_code_unique'));

        // Another super admin's school with the same code lands between this
        // save's check and its insert.
        Organization::creating(function () {
            DB::table('organizations')->insert(['name' => 'Other', 'email' => 'o@test.in', 'school_code' => 'RACE', 'serial_number' => '7']);
        });

        $c = $this->form('RACE');
        $c->modalStep = 2;
        $c->saveSchool();

        $this->assertSame(1, $c->modalStep);
        $this->assertSame('The school code has already been taken.', $c->getErrorBag()->first('schoolCode'));
        $this->assertLessThanOrEqual(1, DB::table('organizations')->where('school_code', 'RACE')->count());
    }
}
