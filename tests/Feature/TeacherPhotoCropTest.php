<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminTeacherController;
use App\Http\Controllers\v1\TeacherController;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Teachers' photos cut to the passport frame (35 × 45 mm) of the app's photo
 * selector: the admin app's Add / Edit Teacher form and the teacher's page
 * (a new photo, or the saved one cropped), and a teacher's own profile photo.
 * The app sends the part as crop_x … crop_h; the server cuts it (PhotoCrop).
 */
class TeacherPhotoCropTest extends TestCase
{
    private int $org = 4;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Storage::fake('s3');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('username')->nullable();
            $t->string('previous_username')->nullable();
            $t->string('password')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('image')->nullable();
            $t->string('photo_circle')->nullable();
            $t->string('role')->nullable();
            $t->string('gender')->nullable();
            $t->date('dob')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            foreach (['employee_id', 'qualification', 'phone', 'address', 'city', 'state', 'pincode', 'emergency_contact'] as $c) {
                $t->string($c)->nullable();
            }
            $t->date('date_of_joining')->nullable();
            $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('code')->nullable(); $t->string('logo')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('assign_teacher_standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('teacher_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id'); $t->unsignedBigInteger('subject_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('teacher_detail_id'); $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
    }

    private function actAsAdmin(): void
    {
        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    /** A JPEG: the left half red, the right half blue. */
    private function photo(int $w = 400, int $h = 200): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, intdiv($w, 2) - 1, $h - 1, imagecolorallocate($im, 255, 0, 0));
        imagefilledrectangle($im, intdiv($w, 2), 0, $w - 1, $h - 1, imagecolorallocate($im, 0, 0, 255));
        ob_start();
        imagejpeg($im, null, 95);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    /** [width, height, the colour in the middle]. */
    private function look(string $jpeg): array
    {
        $im = imagecreatefromstring($jpeg);
        $c = imagecolorsforindex($im, imagecolorat($im, intdiv(imagesx($im), 2), intdiv(imagesy($im), 2)));
        $out = [imagesx($im), imagesy($im), ($c['red'] > 200 ? 'red' : ($c['blue'] > 200 ? 'blue' : 'other'))];
        imagedestroy($im);

        return $out;
    }

    private function teacher(array $user = [], int $org = 4): TeacherDetail
    {
        $u = User::forceCreate(array_merge([
            'name' => 'Meera', 'email' => 'meera@example.com', 'username' => 'meera', 'mobile_number' => '9876543210',
            'role' => 'teacher', 'organization_id' => $org, 'gender' => 'female', 'dob' => '1990-04-12',
            'image' => 'https://cdn.test/admin/teachers/images/meera.jpg',
        ], $user));

        return TeacherDetail::forceCreate([
            'user_id' => $u->id, 'organization_id' => $org, 'employee_id' => 'T001', 'qualification' => 'M.Sc',
            'phone' => '9876543210', 'address' => 'Main Road', 'pincode' => '110001',
        ]);
    }

    private function storedImage(int $userId): string
    {
        $url = User::find($userId)->image;

        return Storage::disk('s3')->get(ltrim(str_replace('/storage/', '/', (string) parse_url($url, PHP_URL_PATH)), '/'));
    }

    /** The passport frame on the right half of a 400 × 200 photo: 140 × 180, 35 : 45. */
    private const PASSPORT = ['crop_x' => 0.5, 'crop_y' => 0.05, 'crop_w' => 0.35, 'crop_h' => 0.9];

    // ── The admin app: the teacher's page (the photo alone) ─────────────────

    private function photoApi(int $id, array $q = [], array $files = [])
    {
        $this->actAsAdmin();

        return app(AdminTeacherController::class)->photo(Request::create('/', 'POST', $q, [], $files), $id);
    }

    public function test_a_new_photo_is_cut_to_the_passport_frame(): void
    {
        $d = $this->teacher(['image' => null]);
        $file = UploadedFile::fake()->createWithContent('face.jpg', $this->photo());

        $res = $this->photoApi($d->id, self::PASSPORT, ['image' => $file]);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertSame(User::find($d->user_id)->image, $res->getData(true)['data']['image']);
        $this->assertSame([140, 180, 'blue'], $this->look($this->storedImage($d->user_id)));
    }

    public function test_a_new_photo_without_a_frame_is_kept_as_sent(): void
    {
        $d = $this->teacher(['image' => null]);
        $bytes = $this->photo();

        $this->photoApi($d->id, [], ['image' => UploadedFile::fake()->createWithContent('face.jpg', $bytes)]);

        $this->assertSame($bytes, $this->storedImage($d->user_id));
    }

    public function test_the_saved_photo_alone_is_cut_and_the_uncut_one_taken_off(): void
    {
        Http::fake(['cdn.test/*' => Http::response($this->photo())]);
        Storage::disk('s3')->put('admin/teachers/images/meera.jpg', 'old');
        $d = $this->teacher();

        $res = $this->photoApi($d->id, ['crop_x' => 0.05, 'crop_y' => 0.05, 'crop_w' => 0.35, 'crop_h' => 0.9]);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertNotSame('https://cdn.test/admin/teachers/images/meera.jpg', User::find($d->user_id)->image);
        $this->assertSame([140, 180, 'red'], $this->look($this->storedImage($d->user_id)));
        Storage::disk('s3')->assertMissing('admin/teachers/images/meera.jpg');
    }

    public function test_no_photo_to_crop_or_one_that_cannot_be_fetched_is_said(): void
    {
        $none = $this->teacher(['image' => null]);
        $this->assertSame(422, $this->photoApi($none->id, self::PASSPORT)->getStatusCode());
        $this->assertNull(User::find($none->user_id)->image);

        Http::fake(['cdn.test/*' => Http::response('', 500)]);
        $d = $this->teacher(['email' => 'r@example.com', 'username' => 'rekha']);
        $this->assertSame(422, $this->photoApi($d->id, self::PASSPORT)->getStatusCode());
        $this->assertSame('https://cdn.test/admin/teachers/images/meera.jpg', User::find($d->user_id)->image);
    }

    public function test_another_school_s_teacher_is_not_found(): void
    {
        $other = $this->teacher([], 5);

        $this->assertSame(404, $this->photoApi($other->id, self::PASSPORT)->getStatusCode());
    }

    // ── The admin app: Add / Edit Teacher ────────────────────────────────────

    private function fields(array $over = []): array
    {
        return array_merge([
            'name' => 'Meera', 'email' => 'meera@example.com', 'username' => 'meera', 'mobile' => '9876543210',
            'dob' => '1990-04-12', 'gender' => 'female', 'employee_id' => 'T001', 'qualification' => 'M.Sc',
            'address' => 'Main Road', 'pincode' => '110001', 'is_active' => 1,
        ], $over);
    }

    private function update(int $id, array $over = [], array $files = [])
    {
        $this->actAsAdmin();

        return app(AdminTeacherController::class)->update(Request::create('/', 'POST', $this->fields($over), [], $files), $id);
    }

    public function test_a_new_teacher_s_photo_is_cut_to_the_frame(): void
    {
        $this->actAsAdmin();
        $file = UploadedFile::fake()->createWithContent('face.jpg', $this->photo());

        $res = app(AdminTeacherController::class)->store(Request::create('/', 'POST', $this->fields(self::PASSPORT), [], ['image' => $file]));

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $id = $res->getData(true)['data']['user_id'];
        $this->assertSame([140, 180, 'blue'], $this->look($this->storedImage($id)));
    }

    public function test_an_edit_with_a_new_photo_cuts_it_and_takes_the_old_one_off(): void
    {
        Storage::disk('s3')->put('admin/teachers/images/meera.jpg', 'old');
        $d = $this->teacher();

        $res = $this->update($d->id, self::PASSPORT, ['image' => UploadedFile::fake()->createWithContent('face.jpg', $this->photo())]);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertSame([140, 180, 'blue'], $this->look($this->storedImage($d->user_id)));
        Storage::disk('s3')->assertMissing('admin/teachers/images/meera.jpg');
    }

    public function test_an_edit_with_only_a_frame_cuts_the_saved_photo(): void
    {
        Http::fake(['cdn.test/*' => Http::response($this->photo())]);
        $d = $this->teacher();

        $res = $this->update($d->id, self::PASSPORT);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertSame([140, 180, 'blue'], $this->look($this->storedImage($d->user_id)));
    }

    public function test_a_saved_photo_that_cannot_be_fetched_leaves_the_teacher_as_they_were(): void
    {
        Http::fake(['cdn.test/*' => Http::response('', 500)]);
        $d = $this->teacher();

        $res = $this->update($d->id, array_merge(self::PASSPORT, ['name' => 'Renamed']));

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('https://cdn.test/admin/teachers/images/meera.jpg', User::find($d->user_id)->image);
        $this->assertSame('Meera', User::find($d->user_id)->name);
    }

    public function test_an_edit_without_a_frame_leaves_the_photo(): void
    {
        Http::fake();
        $d = $this->teacher();

        $res = $this->update($d->id, ['name' => 'Renamed']);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertSame('https://cdn.test/admin/teachers/images/meera.jpg', User::find($d->user_id)->image);
        $this->assertSame('Renamed', User::find($d->user_id)->name);
        Http::assertNothingSent();
    }

    // ── The teacher's own profile photo ──────────────────────────────────────

    private function ownPhoto(TeacherDetail $d, array $q, string $bytes)
    {
        $this->actingAs(User::find($d->user_id));

        return app(TeacherController::class)->updateTeacherPhoto(
            Request::create('/', 'POST', $q, [], ['photo' => UploadedFile::fake()->createWithContent('face.jpg', $bytes)])
        );
    }

    public function test_the_teacher_s_own_photo_is_cut_to_the_passport_frame(): void
    {
        $d = $this->teacher(['image' => null]);

        $res = $this->ownPhoto($d, self::PASSPORT, $this->photo());

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertSame(User::find($d->user_id)->image, $res->getData(true)['data']['personal_info']['image']);
        $this->assertSame([140, 180, 'blue'], $this->look($this->storedImage($d->user_id)));
    }

    public function test_an_older_app_s_square_is_still_a_square_and_none_the_centre(): void
    {
        $d = $this->teacher(['image' => null]);

        // The earlier cropper's square: the left half of the photo.
        $this->ownPhoto($d, ['crop_x' => 0, 'crop_y' => 0, 'crop_w' => 0.5, 'crop_h' => 1], $this->photo());
        $this->assertSame([200, 200, 'red'], $this->look($this->storedImage($d->user_id)));

        // No part sent: the centre square, at most 512px.
        $this->ownPhoto($d, [], $this->photo(2000, 1000));
        $this->assertSame([512, 512], array_slice($this->look($this->storedImage($d->user_id)), 0, 2));
    }
}
