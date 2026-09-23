<?php

namespace Tests\Feature;

use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\LoginIdentifier;
use App\Support\Usernames;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Who gets in with what, now that an email may belong to several people: a
 * student is their admission number, a teacher their username, and the
 * school's own staff their address.
 */
class LoginIdentifierTest extends TestCase
{
    private int $org = 9;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('username', 30)->nullable();
            $t->string('role')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->timestamps();
        });
    }

    private function user(string $name, string $role, ?string $email, ?string $username = null): User
    {
        return User::forceCreate([
            'name' => $name, 'email' => $email, 'username' => $username,
            'role' => $role, 'organization_id' => $this->org,
        ]);
    }

    private function student(string $name, string $admissionNo, string $email): User
    {
        $user = $this->user($name, 'user', $email);
        StudentDetail::forceCreate([
            'user_id' => $user->id, 'organization_id' => $this->org,
            'full_name' => $name, 'admission_no' => $admissionNo,
        ]);

        return $user;
    }

    public function test_two_students_and_two_teachers_may_share_one_email(): void
    {
        $one = $this->student('Aarav', 'ADM-1', 'family@example.com');
        $two = $this->student('Isha', 'ADM-2', 'family@example.com');
        $this->user('Meera', 'teacher', 'staffroom@example.com', 'meera.sharma');
        $this->user('Rahul', 'teacher', 'staffroom@example.com', 'rahul.verma');

        // Each is found by what is theirs alone.
        $this->assertSame($one->id, LoginIdentifier::resolve('ADM-1')[0]?->id);
        $this->assertSame($two->id, LoginIdentifier::resolve('ADM-2')[0]?->id);
        $this->assertSame('meera.sharma', LoginIdentifier::resolve('meera.sharma')[0]?->username);
        $this->assertSame('rahul.verma', LoginIdentifier::resolve('rahul.verma')[0]?->username);

        // The shared address cannot say which of them it is.
        [$user, $why] = LoginIdentifier::resolve('staffroom@example.com');
        $this->assertNull($user);
        $this->assertStringContainsString('username', $why);

        [$user, $why] = LoginIdentifier::resolve('family@example.com');
        $this->assertNull($user);
        $this->assertStringContainsString('admission number', $why);
    }

    public function test_a_lone_teacher_still_gets_in_with_their_email(): void
    {
        $meera = $this->user('Meera', 'teacher', 'meera@example.com', 'meera.sharma');

        $this->assertSame($meera->id, LoginIdentifier::resolve('meera@example.com')[0]?->id);
    }

    public function test_staff_keep_their_address_and_win_it(): void
    {
        $admin = $this->user('Head', 'admin', 'office@example.com');
        $this->user('Also Teacher', 'teacher', 'office@example.com', 'also.teacher');

        $this->assertSame($admin->id, LoginIdentifier::resolve('office@example.com')[0]?->id);
    }

    public function test_only_a_school_accounts_address_is_off_limits(): void
    {
        $this->user('Head', 'admin', 'office@example.com');
        $this->student('Aarav', 'ADM-1', 'family@example.com');
        $this->user('Meera', 'teacher', 'staffroom@example.com', 'meera.sharma');

        $this->assertTrue(LoginIdentifier::emailReserved('office@example.com'));
        $this->assertFalse(LoginIdentifier::emailReserved('family@example.com'));
        $this->assertFalse(LoginIdentifier::emailReserved('staffroom@example.com'));
        $this->assertFalse(LoginIdentifier::emailReserved('new@example.com'));
    }

    public function test_nothing_matches(): void
    {
        [$user, $why] = LoginIdentifier::resolve('nobody-here');
        $this->assertNull($user);
        $this->assertStringContainsString('No account found', $why);
    }

    public function test_the_address_a_code_goes_to_is_only_half_said(): void
    {
        $this->assertSame('ra****ta@gmail.com', LoginIdentifier::maskEmail('rajeshgupta@gmail.com'));
        $this->assertSame('a**@x.com', LoginIdentifier::maskEmail('ab@x.com'));
    }

    public function test_a_username_must_be_free_and_well_formed(): void
    {
        $this->user('Meera', 'teacher', 'meera@example.com', 'meera.sharma');

        $this->assertSame([], Usernames::problems('rahul.verma'));

        $taken = Usernames::problems('meera.sharma');
        $this->assertCount(1, $taken);
        $this->assertStringContainsString('already has this username', $taken[0]);
        $this->assertStringContainsString('meera.sharma1', $taken[0]);

        $bad = Usernames::problems('7up');
        $this->assertStringContainsString('4 to 50 characters', implode(' ', $bad));
        $this->assertStringContainsString('start with a letter', implode(' ', $bad));

        // Editing that same teacher leaves their own username alone.
        $meera = User::where('username', 'meera.sharma')->first();
        $this->assertSame([], Usernames::problems('meera.sharma', $meera->id));
    }

    public function test_a_school_username_is_the_first_name_at_the_school_code(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('school_code')->nullable();
            $t->timestamps();
        });
        \Illuminate\Support\Facades\DB::table('organizations')->insert(['id' => $this->org, 'school_code' => 'TDS']);

        $this->assertSame('meera@tds', Usernames::suggest('Meera Sharma', null, $this->org));

        // A second Meera in the school is meera2@tds, a third meera3@tds.
        $this->user('Meera Sharma', 'teacher', 'a@example.com', 'meera@tds');
        $this->assertSame('meera2@tds', Usernames::forSchool('Meera Gupta', $this->org));
        $this->user('Meera Gupta', 'teacher', 'b@example.com', 'meera2@tds');
        $this->assertSame('meera3@tds', Usernames::forSchool('Meera Rao', $this->org));

        // It is a valid username, and it signs the teacher in.
        $this->assertSame([], Usernames::problems('meera3@tds'));
        $this->assertMatchesRegularExpression(Usernames::REGEX, 'meera2@tds');
        $this->assertSame('meera@tds', LoginIdentifier::resolve('Meera@TDS')[0]?->username);

        // Taken: the free ones offered carry the number before the @.
        $this->assertStringContainsString('meera3@tds', implode(' ', Usernames::problems('meera@tds')));
    }

    public function test_an_existing_teacher_is_given_one_from_their_name(): void
    {
        $this->user('Meera Sharma', 'teacher', 'one@example.com', 'meera.sharma');

        // The same name again lands beside it, not on it.
        $this->assertSame('meera.sharma1', Usernames::suggest('Meera Sharma', 'two@example.com'));

        // Nothing usable in the name — the email carries it.
        $this->assertSame('rk.gupta', Usernames::suggest('   ', 'rk.gupta@example.com'));
    }
}
