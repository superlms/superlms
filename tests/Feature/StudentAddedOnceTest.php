<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminStudentController;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\StudentDuplicates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Add Student pressed twice, or pressed again after the network lost the
 * answer, adds the child once: one admission number, one welcome on WhatsApp
 * and by email. A brother or sister, and the same child long after, are not
 * taken for that child.
 *
 * The test database cannot mint an admission number (that SQL is MySQL's), so
 * the first press's student is laid down here as store() leaves it, and the
 * second press goes through store().
 */
class StudentAddedOnceTest extends TestCase
{
    private int $org = 4;
    private AdminStudentController $api;

    protected function setUp(): void
    {
        parent::setUp();

        // The welcome email and WhatsApp are queued after the response; faked,
        // they are only counted.
        Bus::fake();
        config([
            'services.zeptomail.student_password_template_key' => 'tpl',
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 't',
            'services.whatsapp.phone_number_id' => 'p',
        ]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('school_code')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('password')->nullable();
            $t->string('image')->nullable();
            $t->string('role')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('code')->nullable();
            $t->string('board')->nullable(); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            foreach (['full_name', 'father_name', 'mother_name', 'email', 'gender', 'religion', 'local_address', 'permanent_address',
                      'city', 'state', 'pincode', 'admission_no', 'roll_no', 'board', 'aadhar_no', 'phone', 'image', 'appar_id', 'registration_number'] as $c) {
                $t->string($c)->nullable();
            }
            $t->date('dob')->nullable();
            $t->date('date_of_admission')->nullable();
            $t->boolean('transportation_required')->default(false);
            $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Test School', 'school_code' => 'TDS']);
        DB::table('standards')->insert(['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 3', 'code' => '03', 'board' => 'CBSE']);
        DB::table('sections')->insert(['id' => 2, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'A']);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);

        $this->api = app(AdminStudentController::class);
    }

    private function add(array $over = [], array $files = [])
    {
        return $this->api->store(Request::create('/', 'POST', array_merge([
            'name' => 'Aarav Sharma', 'email' => 'parent@example.com', 'mobile' => '9876543210',
            'dob' => '2016-04-12', 'gender' => 'male', 'standard_id' => 1, 'section_id' => 2,
            'father_name' => 'Rakesh Sharma', 'is_active' => 1, 'transportation_required' => 0,
        ], $over), [], $files));
    }

    /** The student the first press saved. */
    private function firstPress(): StudentDetail
    {
        $user = User::forceCreate(['name' => 'Aarav Sharma', 'email' => 'parent@example.com', 'mobile_number' => '9876543210',
            'role' => 'user', 'organization_id' => $this->org]);

        return StudentDetail::forceCreate([
            'user_id' => $user->id, 'organization_id' => $this->org, 'standard_id' => 1, 'section_id' => 2,
            'full_name' => 'Aarav Sharma', 'father_name' => 'Rakesh Sharma', 'dob' => '2016-04-12',
            'phone' => '9876543210', 'admission_no' => '26TDS160001', 'roll_no' => '301',
        ]);
    }

    private function same(array $over = []): ?StudentDetail
    {
        $a = array_merge(['org' => $this->org, 'class' => 1, 'name' => 'Aarav Sharma', 'father' => 'Rakesh Sharma',
            'dob' => '2016-04-12', 'mobile' => '9876543210'], $over);

        return StudentDuplicates::recent($a['org'], $a['class'], $a['name'], $a['father'], $a['dob'], $a['mobile']);
    }

    /** The welcome email and WhatsApp sent so far. */
    private function welcomes(): int
    {
        return count(Bus::dispatchedAfterResponse(CallQueuedClosure::class));
    }

    public function test_the_same_child_sent_again_is_the_one_already_added(): void
    {
        $first = $this->firstPress();

        $again = $this->add()->getData(true);

        $this->assertSame($first->id, $again['data']['id']);
        $this->assertSame('26TDS160001', $again['data']['admission_no']);
        $this->assertTrue($again['data']['already_added']);
        $this->assertSame('Student Created Successfully!', $again['message'], 'an older app reads it as added');

        $this->assertSame(1, StudentDetail::count());
        $this->assertSame(1, User::where('role', 'user')->count());
        $this->assertSame(0, $this->welcomes(), 'no second email or WhatsApp');
    }

    public function test_the_photo_sent_again_is_not_kept(): void
    {
        Storage::fake('s3');
        $this->firstPress();

        $this->add([], ['image' => UploadedFile::fake()->image('face.jpg', 200, 200)]);

        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    public function test_the_app_s_form_sent_again_is_that_student_even_with_a_change(): void
    {
        $first = $this->firstPress();
        StudentDuplicates::remember($this->org, 'form-1', $first);

        // The name corrected after "Not saved", then sent again from the same form.
        $again = $this->add(['client_ref' => 'form-1', 'name' => 'Aarav Sharmaa'])->getData(true);

        $this->assertSame($first->id, $again['data']['id']);
        $this->assertTrue($again['data']['already_added']);
        $this->assertSame(1, StudentDetail::count());
        $this->assertSame(0, $this->welcomes());
    }

    public function test_a_form_s_mark_is_its_own(): void
    {
        $first = $this->firstPress();
        StudentDuplicates::remember($this->org, 'form-1', $first);

        $this->assertSame($first->id, StudentDuplicates::forRef($this->org, 'form-1')?->id);
        $this->assertNull(StudentDuplicates::forRef(99, 'form-1'), 'another school');
        $this->assertNull(StudentDuplicates::forRef($this->org, 'form-2'));
        $this->assertNull(StudentDuplicates::forRef($this->org, ''));
        $this->assertNull(StudentDuplicates::forRef($this->org, null));
    }

    public function test_only_the_very_same_child_counts(): void
    {
        $first = $this->firstPress();

        $this->assertSame($first->id, $this->same()?->id);
        // The panel keeps what was typed: a space more or less is the same.
        $this->assertSame($first->id, $this->same(['name' => 'Aarav Sharma ', 'father' => ' Rakesh Sharma'])?->id);

        // A brother or sister — same father, mobile, even birthday — is not.
        $this->assertNull($this->same(['name' => 'Anaya Sharma']));
        $this->assertNull($this->same(['father' => 'Mahesh Sharma']));
        $this->assertNull($this->same(['dob' => '2016-04-13']));
        $this->assertNull($this->same(['mobile' => '9123456780']));
        $this->assertNull($this->same(['class' => 2]));
        $this->assertNull($this->same(['org' => 99]), 'another school');
        $this->assertNull($this->same(['name' => '']));
    }

    public function test_the_same_child_long_after_is_not_taken_for_this_add(): void
    {
        $this->firstPress();
        StudentDetail::query()->update(['created_at' => now()->subMinutes(StudentDuplicates::WINDOW_MINUTES + 1)]);

        $this->assertNull($this->same());
    }

    public function test_every_way_of_adding_a_student_asks_first(): void
    {
        foreach (['app/Livewire/Admin/Student.php', 'app/Livewire/SuperAdmin/Student.php', 'app/Http/Controllers/v1/AdminStudentController.php'] as $file) {
            $this->assertStringContainsString('StudentDuplicates::recent(', file_get_contents(base_path($file)), $file);
        }
    }
}
