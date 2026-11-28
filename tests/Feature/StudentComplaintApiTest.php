<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\StudentComplaintController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The student app's Complaints: a student sees the complaints the school has
 * recorded about them, newest first, and nobody else's; only a student asks.
 */
class StudentComplaintApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('school_complaints', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('title'); $t->text('description'); $t->string('file_path')->nullable(); $t->string('file_name')->nullable();
            $t->unsignedBigInteger('added_by')->nullable(); $t->timestamps();
        });
    }

    private function student(string $name): array
    {
        $user = User::forceCreate(['name' => $name, 'role' => 'user', 'organization_id' => 9]);
        $sd = DB::table('student_details')->insertGetId(['user_id' => $user->id, 'organization_id' => 9]);

        return [$user, $sd];
    }

    public function test_a_student_sees_their_own_complaints_newest_first(): void
    {
        [$me, $mine] = $this->student('Aarav');
        [, $other] = $this->student('Kabir');

        DB::table('school_complaints')->insert([
            ['organization_id' => 9, 'student_detail_id' => $mine, 'title' => 'Late again', 'description' => 'Came at 9:10.', 'created_at' => now()->subDay()],
            ['organization_id' => 9, 'student_detail_id' => $other, 'title' => 'Not mine', 'description' => 'x', 'created_at' => now()],
            ['organization_id' => 9, 'student_detail_id' => $mine, 'title' => 'No homework', 'description' => 'Maths.', 'created_at' => now()],
        ]);
        Auth::setUser($me);

        $res = app(StudentComplaintController::class)->index();
        $this->assertSame(200, $res->status());
        $list = $res->getData(true)['data']['complaints'];

        $this->assertSame(['No homework', 'Late again'], array_column($list, 'title'));
        $this->assertNull($list[0]['file_url']);
        $this->assertFalse($list[0]['is_image']);
    }

    public function test_only_a_student_may_ask(): void
    {
        Auth::setUser(User::forceCreate(['name' => 'Meera', 'role' => 'teacher', 'organization_id' => 9]));

        $this->assertSame(403, app(StudentComplaintController::class)->index()->status());
    }
}
