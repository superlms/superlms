<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\StudentPhotoController;
use App\Http\Controllers\Admin\TeacherPhotoController;
use App\Http\Controllers\v1\AdminStudentController;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\PhotoCircle;
use App\Support\PhotoCrop;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Students' photos: the web panel's list draws a small square of each from
 * this site (not the whole camera shot), the cropper fetches the saved photo
 * from this site, and the class teacher's app can send a square to cut a new
 * or the saved photo to.
 */
class StudentPhotoCropTest extends TestCase
{
    private int $org = 4;
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Storage::fake('s3');

        // The small squares are kept on disk; here, in a folder of the test's own.
        $this->storage = sys_get_temp_dir() . '/lms-photo-test-' . uniqid();
        $this->app->useStoragePath($this->storage);

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('image')->nullable();
            $t->string('photo_circle')->nullable();
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
        DB::table('standards')->insert(['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 3', 'code' => '03', 'board' => 'CBSE']);
        DB::table('sections')->insert(['id' => 2, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'A']);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    /** A JPEG: the left half red, the right half blue; an EXIF orientation if asked. */
    private function photo(int $w = 400, int $h = 200, int $orientation = 1): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, intdiv($w, 2) - 1, $h - 1, imagecolorallocate($im, 255, 0, 0));
        imagefilledrectangle($im, intdiv($w, 2), 0, $w - 1, $h - 1, imagecolorallocate($im, 0, 0, 255));
        ob_start();
        imagejpeg($im, null, 95);
        $jpeg = (string) ob_get_clean();
        imagedestroy($im);

        if ($orientation === 1) {
            return $jpeg;
        }

        // An APP1 segment with one tag: Orientation.
        $tiff = 'II' . pack('v', 42) . pack('V', 8) . pack('v', 1)
            . pack('vvVvv', 0x0112, 3, 1, $orientation, 0) . pack('V', 0);
        $app1 = "Exif\0\0" . $tiff;

        return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2);
    }

    /** [width, height, the colour at a fraction of it as "r,g,b"]. */
    private function look(string $jpeg, float $fx = 0.5, float $fy = 0.5): array
    {
        $im = imagecreatefromstring($jpeg);
        $c = imagecolorsforindex($im, imagecolorat($im, (int) (imagesx($im) * $fx), (int) (imagesy($im) * $fy)));
        $out = [imagesx($im), imagesy($im), ($c['red'] > 200 ? 'red' : ($c['blue'] > 200 ? 'blue' : 'other'))];
        imagedestroy($im);

        return $out;
    }

    private function student(array $user = [], int $org = 4): StudentDetail
    {
        $u = User::forceCreate(array_merge(['name' => 'Aarav', 'email' => 'p@example.com', 'mobile_number' => '9876543210',
            'role' => 'user', 'organization_id' => $org, 'image' => 'https://cdn.test/admin/students/images/aarav.jpg'], $user));

        return StudentDetail::forceCreate([
            'user_id' => $u->id, 'organization_id' => $org, 'standard_id' => 1, 'section_id' => 2, 'full_name' => 'Aarav',
            'father_name' => 'Rakesh', 'dob' => '2016-04-12', 'gender' => 'male', 'phone' => '9876543210',
            'admission_no' => '26TDS160001', 'roll_no' => '301',
        ]);
    }

    private function photoRoute($userId, array $query = [])
    {
        return app(StudentPhotoController::class)->show(Request::create('/', 'GET', $query), $this->org, $userId);
    }

    // ── The web panel's list and cropper ─────────────────────────────────────

    public function test_the_list_gets_a_small_square_kept_by_the_browser(): void
    {
        Http::fake(['cdn.test/*' => Http::response($this->photo(4000, 2250))]);
        $d = $this->student();

        $res = $this->photoRoute($d->user_id, ['size' => 96]);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('image/jpeg', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('immutable', $res->headers->get('Cache-Control'));
        $this->assertSame([96, 96], array_slice($this->look($res->getContent()), 0, 2));

        // Cut once: the next ask is read back without fetching.
        $this->photoRoute($d->user_id, ['size' => 96]);
        Http::assertSentCount(1);
    }

    /** A tall photo: red on top (the first 120 rows of 400), blue under it. */
    private function tall(): string
    {
        $im = imagecreatetruecolor(200, 400);
        imagefilledrectangle($im, 0, 0, 199, 119, imagecolorallocate($im, 255, 0, 0));
        imagefilledrectangle($im, 0, 120, 199, 399, imagecolorallocate($im, 0, 0, 255));
        ob_start();
        imagejpeg($im, null, 95);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    public function test_the_list_s_circle_is_the_top_of_the_photo_and_the_pdf_s_the_middle(): void
    {
        Http::fake(['cdn.test/*' => Http::response($this->tall())]);
        $d = $this->student();

        // The list (a=top): the top square — a quarter of the way down, still red.
        $list = $this->photoRoute($d->user_id, ['size' => 96, 'a' => 'top'])->getContent();
        $this->assertSame('red', $this->look($list, 0.5, 0.25)[2]);

        // Without it (and the PDFs): the middle square, as before — blue there.
        $middle = $this->photoRoute($d->user_id, ['size' => 96])->getContent();
        $this->assertSame('blue', $this->look($middle, 0.5, 0.25)[2]);
        $pdf = \App\Support\PdfPhotos::squares(['https://cdn.test/admin/students/images/aarav.jpg'], 96);
        $this->assertSame('blue', $this->look(array_values($pdf)[0], 0.5, 0.25)[2]);
    }

    public function test_the_list_s_address_changes_with_the_photo(): void
    {
        $d = $this->student();
        $user = User::find($d->user_id);

        $a = StudentPhotoController::thumbUrl($user);
        $this->assertStringContainsString("/{$this->org}/student/{$user->id}/photo?size=96&a=top&v=", $a);

        $user->image = 'https://cdn.test/admin/students/images/new.jpg';
        $this->assertNotSame($a, StudentPhotoController::thumbUrl($user));

        $user->image = null;
        $this->assertNull(StudentPhotoController::thumbUrl($user));
    }

    public function test_a_photo_that_cannot_be_fetched_falls_back_to_itself(): void
    {
        Http::fake(['cdn.test/*' => Http::response('', 404)]);
        $d = $this->student();

        $res = $this->photoRoute($d->user_id, ['size' => 96]);

        $this->assertTrue($res->isRedirect('https://cdn.test/admin/students/images/aarav.jpg'));
    }

    public function test_the_cropper_gets_the_photo_itself_from_this_site(): void
    {
        $bytes = $this->photo(800, 600);
        Http::fake(['cdn.test/*' => Http::response($bytes, 200, ['Content-Type' => 'image/jpeg'])]);
        $d = $this->student();

        $res = $this->photoRoute($d->user_id);

        $this->assertSame($bytes, $res->getContent());
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
    }

    public function test_only_a_student_of_this_school_with_a_photo(): void
    {
        $other   = $this->student([], 99);
        $teacher = $this->student(['role' => 'teacher', 'email' => 't@example.com']);
        $none    = $this->student(['image' => null, 'email' => 'n@example.com']);

        foreach ([$other->user_id, $teacher->user_id, $none->user_id, 12345] as $id) {
            try {
                $this->photoRoute($id, ['size' => 96]);
                $this->fail("user {$id} should not be found");
            } catch (HttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
    }

    // ── The square, cut ──────────────────────────────────────────────────────

    public function test_the_square_is_cut_where_the_app_set_it(): void
    {
        $left  = PhotoCrop::cut($this->photo(), [0.0, 0.0, 0.5, 1.0]);
        $right = PhotoCrop::cut($this->photo(), [0.5, 0.0, 0.5, 1.0]);

        // A 200 px square of a 400 × 200 photo: kept at 200, never blown up.
        $this->assertSame([200, 200, 'red'], $this->look($left));
        $this->assertSame([200, 200, 'blue'], $this->look($right));
        // A big one is brought down to 1024 on its longer side.
        $this->assertSame([1024, 1024, 'blue'], $this->look(PhotoCrop::cut($this->photo(4000, 2000), [0.5, 0.0, 0.5, 1.0])));
    }

    public function test_a_part_cut_from_any_side_keeps_its_shape(): void
    {
        // The left quarter of a 400 × 200 photo: 100 × 200, red.
        $this->assertSame([100, 200, 'red'], $this->look(PhotoCrop::cut($this->photo(), [0.0, 0.0, 0.25, 1.0])));
        // A wide strip off the right of a big one: 2000 × 500 → 1024 × 256.
        $this->assertSame([1024, 256, 'blue'], $this->look(PhotoCrop::cut($this->photo(4000, 2000), [0.5, 0.5, 0.5, 0.25])));
    }

    public function test_a_photo_taken_sideways_is_cut_as_the_phone_shows_it(): void
    {
        // Stored 400 × 200 with "turn it a quarter clockwise": it shows 200 × 400,
        // the red half on top.
        $jpeg = $this->photo(400, 200, 6);

        $this->assertSame('red', $this->look(PhotoCrop::cut($jpeg, [0.0, 0.0, 1.0, 0.5]))[2]);
        $this->assertSame('blue', $this->look(PhotoCrop::cut($jpeg, [0.0, 0.5, 1.0, 0.5]))[2]);
    }

    public function test_only_a_whole_square_inside_the_photo_is_taken(): void
    {
        $req = fn (array $q) => PhotoCrop::fromRequest(Request::create('/', 'POST', $q));

        $this->assertNull($req([]), 'older apps send none');
        $this->assertNull($req(['crop_x' => 0.1, 'crop_y' => 0.1, 'crop_w' => 0.5]));
        $this->assertNull($req(['crop_x' => 'a', 'crop_y' => 0, 'crop_w' => 0.5, 'crop_h' => 0.5]));
        $this->assertNull($req(['crop_x' => 0, 'crop_y' => 0, 'crop_w' => 0, 'crop_h' => 0.5]));
        $this->assertSame([0.25, 0.0, 0.5, 1.0], $req(['crop_x' => '0.25', 'crop_y' => '0', 'crop_w' => '0.5', 'crop_h' => '1']));
        // Kept inside the photo.
        $this->assertEqualsWithDelta([0.8, 0.0, 0.2, 1.0], $req(['crop_x' => 0.8, 'crop_y' => -1, 'crop_w' => 0.5, 'crop_h' => 2]), 1e-9);
    }

    // ── The class teacher's app ──────────────────────────────────────────────

    private function update(int $id, array $over = [], array $files = [])
    {
        return app(AdminStudentController::class)->update(Request::create('/', 'POST', array_merge([
            'name' => 'Aarav', 'email' => 'p@example.com', 'mobile' => '9876543210', 'dob' => '2016-04-12',
            'gender' => 'male', 'standard_id' => 1, 'section_id' => 2, 'father_name' => 'Rakesh', 'is_active' => 1,
            'transportation_required' => 0,
        ], $over), [], $files), $id);
    }

    private function storedImage(int $userId): string
    {
        $url = User::find($userId)->image;

        return Storage::disk('s3')->get(ltrim(str_replace('/storage/', '/', (string) parse_url($url, PHP_URL_PATH)), '/'));
    }

    public function test_a_new_photo_with_a_square_is_saved_cut(): void
    {
        $d = $this->student(['image' => null]);
        $file = UploadedFile::fake()->createWithContent('face.jpg', $this->photo());

        $res = $this->update($d->id, ['crop_x' => 0.5, 'crop_y' => 0, 'crop_w' => 0.5, 'crop_h' => 1], ['image' => $file]);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertSame([200, 200, 'blue'], $this->look($this->storedImage($d->user_id)));
    }

    public function test_a_new_photo_without_a_square_is_saved_as_sent(): void
    {
        $d = $this->student(['image' => null]);
        $bytes = $this->photo();

        $this->update($d->id, [], ['image' => UploadedFile::fake()->createWithContent('face.jpg', $bytes)]);

        $this->assertSame($bytes, $this->storedImage($d->user_id));
    }

    public function test_the_saved_photo_alone_is_cut_to_the_square(): void
    {
        Http::fake(['cdn.test/*' => Http::response($this->photo())]);
        $d = $this->student();

        $res = $this->update($d->id, ['crop_x' => 0, 'crop_y' => 0, 'crop_w' => 0.5, 'crop_h' => 1]);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertNotSame('https://cdn.test/admin/students/images/aarav.jpg', User::find($d->user_id)->image);
        $this->assertSame([200, 200, 'red'], $this->look($this->storedImage($d->user_id)));
    }

    public function test_a_saved_photo_that_cannot_be_fetched_is_left_and_nothing_is_saved(): void
    {
        Http::fake(['cdn.test/*' => Http::response('', 500)]);
        $d = $this->student();

        $res = $this->update($d->id, ['name' => 'Renamed', 'crop_x' => 0, 'crop_y' => 0, 'crop_w' => 0.5, 'crop_h' => 1]);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('https://cdn.test/admin/students/images/aarav.jpg', User::find($d->user_id)->image);
        $this->assertSame('Aarav', User::find($d->user_id)->name);
    }

    public function test_an_edit_without_a_square_leaves_the_photo(): void
    {
        Http::fake();
        $d = $this->student();

        $this->update($d->id, ['name' => 'Renamed']);

        $this->assertSame('https://cdn.test/admin/students/images/aarav.jpg', User::find($d->user_id)->image);
        $this->assertSame('Renamed', User::find($d->user_id)->name);
        Http::assertNothingSent();
    }

    // ── The photo alone (the teacher app's list: crop and Save) ──────────────

    private function photoApi(int $id, array $q = [], array $files = [])
    {
        return app(AdminStudentController::class)->photo(Request::create('/', 'POST', $q, [], $files), $id);
    }

    public function test_the_saved_photo_is_cut_from_the_photo_endpoint(): void
    {
        Http::fake(['cdn.test/*' => Http::response($this->photo())]);
        $d = $this->student();

        $res = $this->photoApi($d->id, ['crop_x' => 0, 'crop_y' => 0, 'crop_w' => 0.25, 'crop_h' => 1]);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertSame(User::find($d->user_id)->image, $res->getData(true)['data']['image']);
        $this->assertSame([100, 200, 'red'], $this->look($this->storedImage($d->user_id)));
    }

    public function test_no_photo_to_crop_is_said(): void
    {
        $d = $this->student(['image' => null]);

        $res = $this->photoApi($d->id, ['crop_x' => 0, 'crop_y' => 0, 'crop_w' => 0.5, 'crop_h' => 1]);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertNull(User::find($d->user_id)->image);
    }

    public function test_a_new_photo_at_the_photo_endpoint_is_cut_or_kept_as_sent(): void
    {
        $d = $this->student(['image' => null]);
        $bytes = $this->photo();

        $this->photoApi($d->id, ['crop_x' => 0.5, 'crop_y' => 0, 'crop_w' => 0.5, 'crop_h' => 1],
            ['image' => UploadedFile::fake()->createWithContent('face.jpg', $bytes)]);
        $this->assertSame([200, 200, 'blue'], $this->look($this->storedImage($d->user_id)));

        // As before: no part asked for, the photo as sent.
        $this->photoApi($d->id, [], ['image' => UploadedFile::fake()->createWithContent('face.jpg', $bytes)]);
        $this->assertSame($bytes, $this->storedImage($d->user_id));
    }

    public function test_the_teachers_page_gets_a_teacher_s_photo_and_no_student_s(): void
    {
        $bytes = $this->photo(800, 600);
        Http::fake(['cdn.test/*' => Http::response($bytes, 200, ['Content-Type' => 'image/jpeg'])]);
        $teacher = $this->student(['role' => 'teacher', 'email' => 't@example.com']);
        $student = $this->student(['email' => 's@example.com']);

        $res = app(TeacherPhotoController::class)->show(Request::create('/'), $this->org, $teacher->user_id);
        $this->assertSame($bytes, $res->getContent());
        $this->assertStringContainsString("/{$this->org}/teacher/{$teacher->user_id}/photo",
            route('admin.teacher.photo', ['organization' => $this->org, 'user' => $teacher->user_id]));

        try {
            app(TeacherPhotoController::class)->show(Request::create('/'), $this->org, $student->user_id);
            $this->fail('a student is not a teacher');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    // ── Profile: the circle the lists show, the photo left as it is ─────────

    public function test_a_circle_set_on_the_photo_is_its_own_until_the_photo_changes(): void
    {
        $d = $this->student();
        $user = User::find($d->user_id);
        $this->assertNull(PhotoCircle::of($user));

        PhotoCircle::store($user, [0.5, 0, 0.5, 1]);
        $this->assertSame([0.5, 0.0, 0.5, 1.0], PhotoCircle::of(User::find($d->user_id)));
        $this->assertSame('https://cdn.test/admin/students/images/aarav.jpg', User::find($d->user_id)->image);

        // A new (or cropped) photo: the lists show its top again.
        $user->image = 'https://cdn.test/admin/students/images/new.jpg';
        $user->save();
        $this->assertNull(PhotoCircle::of(User::find($d->user_id)));
    }

    public function test_the_list_shows_the_circle_set_and_its_address_follows_it(): void
    {
        // Red on the left half, blue on the right.
        Http::fake(['cdn.test/*' => Http::response($this->photo())]);
        $d = $this->student();
        $user = User::find($d->user_id);
        $before = StudentPhotoController::thumbUrl($user);

        // None set: the top square across the middle — red a quarter in.
        $this->assertSame('red', $this->look($this->photoRoute($d->user_id, ['size' => 96, 'a' => 'top'])->getContent(), 0.25)[2]);

        // The right half's square set: blue all over.
        PhotoCircle::store($user, [0.5, 0, 0.5, 1]);
        $list = $this->photoRoute($d->user_id, ['size' => 96, 'a' => 'top'])->getContent();
        $this->assertSame([96, 96, 'blue'], $this->look($list, 0.25));

        $after = StudentPhotoController::thumbUrl(User::find($d->user_id));
        $this->assertNotSame($before, $after);
        $this->assertStringContainsString("/{$this->org}/student/{$user->id}/photo?size=96&a=top&v=", $after);
    }

    private function circleApi(int $id, array $q = [])
    {
        return app(AdminStudentController::class)->photoCircle(Request::create('/', 'POST', $q), $id);
    }

    public function test_the_app_sets_the_circle_and_the_list_rows_carry_it(): void
    {
        $d = $this->student();

        $res = $this->circleApi($d->id, ['circle_x' => 0.25, 'circle_y' => 0, 'circle_w' => 0.5, 'circle_h' => 1]);

        $this->assertSame(200, $res->getStatusCode(), json_encode($res->getData(true)));
        $this->assertEquals(['x' => 0.25, 'y' => 0, 'w' => 0.5, 'h' => 1], $res->getData(true)['data']['photo_circle']);
        // The photo itself is left as it is.
        $this->assertSame('https://cdn.test/admin/students/images/aarav.jpg', User::find($d->user_id)->image);

        // The list's rows (shapeRow; index() itself counts with MySQL's YEAR).
        $row = (fn ($detail) => $this->shapeRow($detail))->call(app(AdminStudentController::class), StudentDetail::with('user')->find($d->id));
        $this->assertEquals(['x' => 0.25, 'y' => 0, 'w' => 0.5, 'h' => 1], $row['photo_circle']);
        $none = (fn ($detail) => $this->shapeRow($detail))->call(app(AdminStudentController::class), StudentDetail::with('user')->find($this->student(['email' => 'n@example.com'])->id));
        $this->assertNull($none['photo_circle']);
    }

    public function test_no_circle_without_a_photo_or_a_circle(): void
    {
        $none = $this->student(['image' => null]);
        $this->assertSame(422, $this->circleApi($none->id, ['circle_x' => 0, 'circle_y' => 0, 'circle_w' => 0.5, 'circle_h' => 1])->getStatusCode());

        $d = $this->student(['email' => 'q@example.com']);
        $this->assertSame(422, $this->circleApi($d->id, ['circle_x' => 'a'])->getStatusCode());
        $this->assertNull(PhotoCircle::of(User::find($d->user_id)));

        // Another school's student is not found.
        $other = $this->student(['email' => 'o@example.com'], 5);
        $this->assertSame(404, $this->circleApi($other->id, ['circle_x' => 0, 'circle_y' => 0, 'circle_w' => 0.5, 'circle_h' => 1])->getStatusCode());
    }

    public function test_the_web_list_s_profile_saves_the_circle_for_this_school_only(): void
    {
        $d = $this->student();
        $other = $this->student(['email' => 'o@example.com'], 5);

        $page = new \App\Livewire\Admin\Student();
        $page->onImageClick($d->user_id);
        $this->assertNull($page->imageCircle);
        $page->saveListPhotoCircle(0.5, 0, 0.5, 1);
        $this->assertSame([0.5, 0.0, 0.5, 1.0], PhotoCircle::of(User::find($d->user_id)));
        $this->assertEquals(['x' => 0.5, 'y' => 0, 'w' => 0.5, 'h' => 1], $page->imageCircle);

        // Opened again, Profile starts at it.
        $page->onImageClick($d->user_id);
        $this->assertEquals(['x' => 0.5, 'y' => 0, 'w' => 0.5, 'h' => 1], $page->imageCircle);

        $page->imageUserId = $other->user_id;
        $page->saveListPhotoCircle(0, 0, 0.5, 1);
        $this->assertNull(PhotoCircle::of(User::find($other->user_id)));
    }

    public function test_the_teachers_list_lays_the_photo_so_the_circle_fills_it(): void
    {
        $style = PhotoCircle::imgStyle([0.25, 0, 0.5, 1]);
        $this->assertSame('position:absolute;max-width:none;width:200%;height:100%;left:-50%;top:-0%', $style);
    }
}
