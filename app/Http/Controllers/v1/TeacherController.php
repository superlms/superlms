<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function teacherLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $user = User::where('email', $request->email)->where('role', 'teacher')->first();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'No teacher account found with this email address.',
                    401
                );
            }

            if (!Hash::check($request->password, $user->password)) {
                return $this->responseService->errorResponse(
                    'The provided password is incorrect.',
                    401
                );
            }

            if (!$user->is_active) {
                return $this->responseService->errorResponse(
                    'Your account has been deactivated. Please contact support.',
                    403
                );
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->responseService->authResponse(
                new UserResource($user),
                $token,
                'Login successful'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Login failed: ' . $e->getMessage(),
                500
            );
        }
    }

    public function teacherProfile(Request $request)
    {
        try {
            $teacherDetail = $this->teacherDetailFor(Auth::id());

            if (!$teacherDetail) {
                return $this->responseService->errorResponse(
                    'Teacher profile details not found',
                    404
                );
            }

            return $this->responseService->success(
                new TeacherProfileResource($teacherDetail),
                'Teacher profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve teacher profile: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * POST /api/v1/teacher/profile/photo  (multipart: photo, crop_x, crop_y, crop_w, crop_h)
     *
     * Adds or replaces the teacher's own profile photo. The app sends the photo
     * as picked, plus the square framed in its cropper as fractions (0–1) of the
     * upright photo. The square is cut here and stored as a JPEG of at most
     * 512px, in the same S3 folder the admin Teachers form uses.
     */
    public function updateTeacherPhoto(Request $request)
    {
        $user = Auth::user();

        if (!TeacherDetail::where('user_id', $user->id)->exists()) {
            return $this->responseService->errorResponse('Teacher profile details not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'photo'  => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'crop_x' => 'nullable|numeric|between:0,1',
            'crop_y' => 'nullable|numeric|between:0,1',
            'crop_w' => 'nullable|numeric|between:0.001,1',
            'crop_h' => 'nullable|numeric|between:0.001,1',
        ], [
            'photo.required' => 'Choose a photo.',
            'photo.image'    => 'Photo must be an image.',
            'photo.mimes'    => 'Photo must be a JPG, PNG or WebP image.',
            'photo.max'      => 'Photo must be 10 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return $this->responseService->error(
                implode(' ', $validator->errors()->all()),
                422,
                $validator->errors()->toArray()
            );
        }

        // Without a full crop, the centre square is taken.
        $crop = $request->filled(['crop_x', 'crop_y', 'crop_w', 'crop_h'])
            ? array_map('floatval', $request->only(['crop_x', 'crop_y', 'crop_w', 'crop_h']))
            : null;

        try {
            $jpeg = $this->squareAvatar($request->file('photo'), $crop);
            if ($jpeg === null) {
                return $this->responseService->error('That photo could not be read. Try another one.', 422);
            }

            $path = 'admin/teachers/images/' . Str::random(40) . '.jpg';
            Storage::disk('s3')->put($path, $jpeg);
            Storage::disk('s3')->setVisibility($path, 'public');

            $oldImage    = $user->image;
            $user->image = Storage::disk('s3')->url($path);
            $user->save();

            // The old photo goes only once the new one is saved.
            if ($oldImage) {
                try {
                    Storage::disk('s3')->delete(ltrim((string) parse_url($oldImage, PHP_URL_PATH), '/'));
                } catch (\Throwable $e) {
                    // best-effort cleanup
                }
            }

            return $this->responseService->success(
                new TeacherProfileResource($this->teacherDetailFor($user->id)),
                'Profile photo updated'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to update profile photo: ' . $e->getMessage(),
                500
            );
        }
    }

    private function teacherDetailFor(int $userId): ?TeacherDetail
    {
        return TeacherDetail::with([
            'user',
            'assignedSubjects.subject',
            'assignedSubjects.standard',
            'assignedSubjects.section',
            'teacherSections.section.standard',
            'assignedClasses.standard',
            'assignedClasses.section',
            'organization'
        ])
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * JPEG bytes of the square cut from an uploaded photo, at most 512px, or
     * null when GD can't read the file. $crop holds crop_x/crop_y/crop_w/crop_h
     * as fractions of the upright photo; null takes the centre square.
     */
    private function squareAvatar(UploadedFile $file, ?array $crop): ?string
    {
        $path  = $file->getRealPath();
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (!$image) {
            return null;
        }

        // Phones store photos sideways with an EXIF tag saying how to turn them,
        // and the cropper framed the photo the way it is shown. GD ignores the
        // tag, so the pixels are turned upright before anything is measured.
        $orientation = 1;
        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/pjpeg'], true)) {
            $exif        = @exif_read_data($path);
            $orientation = (int) (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1);
        }
        if (in_array($orientation, [2, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        if (in_array($orientation, [4, 5], true)) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }
        $angle = match ($orientation) {
            3       => 180,
            5, 6, 7 => -90,
            8       => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $image = imagerotate($image, $angle, 0) ?: $image;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        if ($crop) {
            $side = (int) round(min($crop['crop_w'] * $width, $crop['crop_h'] * $height));
            $x    = (int) round($crop['crop_x'] * $width);
            $y    = (int) round($crop['crop_y'] * $height);
        } else {
            $side = min($width, $height);
            $x    = intdiv($width - $side, 2);
            $y    = intdiv($height - $side, 2);
        }
        $side = max(1, min($side, $width, $height));
        $x    = max(0, min($x, $width - $side));
        $y    = max(0, min($y, $height - $side));

        $size   = min(512, $side);
        $avatar = imagecreatetruecolor($size, $size);
        // Transparent PNGs land on white rather than black.
        imagefill($avatar, 0, 0, imagecolorallocate($avatar, 255, 255, 255));
        imagecopyresampled($avatar, $image, 0, 0, $x, $y, $size, $size, $side, $side);

        ob_start();
        imagejpeg($avatar, null, 88);

        return ob_get_clean() ?: null;
    }
}
