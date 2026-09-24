<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminStudentController;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The admin app's Student Detail changes or removes a student's photo on its
 * own — nothing else about the student is sent or touched.
 */
class AdminStudentPhotoTest extends TestCase
{
    private int $org = 4;
    private AdminStudentController $api;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->string('role')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->timestamps();
        });

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);

        $this->api = app(AdminStudentController::class);
    }

    private function student(int $org = 4): StudentDetail
    {
        $user = User::forceCreate(['name' => 'Aarav', 'email' => 'aarav@s.test', 'organization_id' => $org, 'image' => 'https://cdn.test/old.jpg']);

        return StudentDetail::forceCreate(['user_id' => $user->id, 'organization_id' => $org, 'standard_id' => 1, 'section_id' => 2, 'full_name' => 'Aarav']);
    }

    public function test_a_new_photo_replaces_the_old_one(): void
    {
        $d = $this->student();
        $req = Request::create('/', 'POST', [], [], ['image' => UploadedFile::fake()->image('face.jpg', 400, 400)]);

        $res = $this->api->photo($req, $d->id)->getData(true);

        $this->assertSame('Photo updated.', $res['message']);
        $this->assertNotSame('https://cdn.test/old.jpg', User::find($d->user_id)->image);
        $this->assertSame(User::find($d->user_id)->image, $res['data']['image']);
        $this->assertSame('Aarav', User::find($d->user_id)->name, 'nothing else changes');
    }

    public function test_a_photo_over_two_megabytes_is_refused(): void
    {
        $d = $this->student();
        $req = Request::create('/', 'POST', [], [], ['image' => UploadedFile::fake()->image('big.jpg')->size(3000)]);

        $res = $this->api->photo($req, $d->id);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('2 MB', $res->getData(true)['message']);
        $this->assertSame('https://cdn.test/old.jpg', User::find($d->user_id)->image);
    }

    public function test_the_photo_can_be_taken_off(): void
    {
        $d = $this->student();

        $res = $this->api->photo(Request::create('/', 'POST', ['remove' => 1]), $d->id)->getData(true);

        $this->assertSame('Photo removed.', $res['message']);
        $this->assertNull(User::find($d->user_id)->image);
    }

    public function test_another_school_s_student_is_not_found(): void
    {
        $d = $this->student(99);

        $this->assertSame(404, $this->api->photo(Request::create('/', 'POST', ['remove' => 1]), $d->id)->getStatusCode());
        $this->assertNotNull(User::find($d->user_id)->image);
    }
}
