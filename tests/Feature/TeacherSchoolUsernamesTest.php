<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginIdentifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Teachers whose username was their name with a number (meera.sharma1) take
 * the school's form, meera@tds, and the old one still signs them in.
 */
class TeacherSchoolUsernamesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('school_code')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('username', 50)->nullable()->unique();
            $t->string('role')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('admission_no')->nullable();
            $t->timestamps();
        });

        DB::table('organizations')->insert([
            ['id' => 4, 'school_code' => 'TDS'],
            ['id' => 5, 'school_code' => 'DMS'],
        ]);
    }

    private function teacher(string $name, ?string $username, ?int $org = 4, string $role = 'teacher'): int
    {
        return User::forceCreate([
            'name' => $name, 'email' => 'staffroom@example.com', 'username' => $username,
            'role' => $role, 'organization_id' => $org,
        ])->id;
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_09_24_000000_give_numbered_teacher_usernames_the_school_code.php'))->up();
    }

    private function usernameOf(int $id): ?string
    {
        return DB::table('users')->where('id', $id)->value('username');
    }

    public function test_numbered_usernames_end_in_the_school_code(): void
    {
        $already = $this->teacher('Meera Gupta', 'meera@tds');
        $meera   = $this->teacher('Meera Sharma', 'meera.sharma');
        $meera2  = $this->teacher('Meera Rao', 'meera.rao1');
        $rahul   = $this->teacher('Rahul Verma', 'rahul.verma');
        $other   = $this->teacher('Rahul Singh', 'rahul.singh', 5);
        $none    = $this->teacher('Anita Das', null);
        $noOrg   = $this->teacher('Loose Teacher', 'loose.teacher', null);
        $admin   = $this->teacher('Head', 'head.office', 4, 'admin');

        $this->migrate();

        // One already in the school's form keeps it; the next Meeras number on.
        $this->assertSame('meera@tds', $this->usernameOf($already));
        $this->assertSame('meera2@tds', $this->usernameOf($meera));
        $this->assertSame('meera3@tds', $this->usernameOf($meera2));
        $this->assertSame('rahul@tds', $this->usernameOf($rahul));
        $this->assertSame('rahul@dms', $this->usernameOf($other));
        $this->assertSame('anita@tds', $this->usernameOf($none));

        // No school to take a code from, or not a teacher: untouched.
        $this->assertSame('loose.teacher', $this->usernameOf($noOrg));
        $this->assertSame('head.office', $this->usernameOf($admin));

        // The old one is kept.
        $this->assertSame('meera.sharma', DB::table('users')->where('id', $meera)->value('previous_username'));
        $this->assertNull(DB::table('users')->where('id', $none)->value('previous_username'));
    }

    public function test_the_old_username_still_signs_the_teacher_in(): void
    {
        $rahul = $this->teacher('Rahul Verma', 'rahul.verma1');

        $this->migrate();

        $this->assertSame($rahul, LoginIdentifier::resolve('rahul@tds', teacherEmail: false)[0]?->id);
        $this->assertSame($rahul, LoginIdentifier::resolve('Rahul.Verma1', teacherEmail: false)[0]?->id);

        [$user, $why] = LoginIdentifier::resolve('rahul.verma', teacherEmail: false);
        $this->assertNull($user);
        $this->assertStringContainsString('No account found', $why);
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $meera = $this->teacher('Meera Sharma', 'meera.sharma1');

        $this->migrate();
        $this->migrate();

        $this->assertSame('meera@tds', $this->usernameOf($meera));
        $this->assertSame('meera.sharma1', DB::table('users')->where('id', $meera)->value('previous_username'));
    }
}
