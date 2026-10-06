<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * A photo cut to the part the app's photo editor was set to.
 *
 * The app (the class teacher's Students) can't cut a picture itself, so it
 * sends the photo and where the part sits on it: crop_x, crop_y, crop_w,
 * crop_h, each a fraction (0–1) of the photo's width or height as it shows on
 * the phone — upright. The part may be any shape (cut from any side) or a
 * square (the earlier circle cropper). The photo is turned upright here the
 * same way (its EXIF orientation) and the part cut out as it is, its longer
 * side at most MAX px. The teacher's own profile photo is cut by
 * v1\TeacherController::squareAvatar, left as it is.
 */
class PhotoCrop
{
    public const MAX = 1024;

    /**
     * The square the request asks for, as [x, y, w, h] fractions kept inside
     * the photo — or null when it asks for none (older apps, the admin app).
     */
    public static function fromRequest(Request $request): ?array
    {
        $keys = ['crop_x', 'crop_y', 'crop_w', 'crop_h'];
        foreach ($keys as $k) {
            if (!is_numeric($request->input($k))) {
                return null;
            }
        }
        [$x, $y, $w, $h] = array_map(fn ($k) => (float) $request->input($k), $keys);

        $x = max(0.0, min(1.0, $x));
        $y = max(0.0, min(1.0, $y));
        $w = min($w, 1.0 - $x);
        $h = min($h, 1.0 - $y);

        return ($w >= 0.01 && $h >= 0.01) ? [$x, $y, $w, $h] : null;
    }

    /** The picture upright, cut to the part (its longer side at most MAX), as JPEG bytes — or null. */
    public static function cut(string $bytes, array $crop): ?string
    {
        $info = @getimagesizefromstring($bytes);
        if (!$info) {
            return null;
        }
        $source = @imagecreatefromstring($bytes);
        if (!$source) {
            return null;
        }

        $orientation = 1;
        if (($info[2] ?? null) === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $stream = fopen('php://memory', 'r+b');
            fwrite($stream, $bytes);
            rewind($stream);
            $exif = @exif_read_data($stream);
            fclose($stream);
            $orientation = (int) ($exif['Orientation'] ?? 1);
        }

        // Turned (and, for the mirrored ones, flipped) the way the phone shows it.
        if (in_array($orientation, [2, 7], true)) {
            imageflip($source, IMG_FLIP_HORIZONTAL);
        }
        if (in_array($orientation, [4, 5], true)) {
            imageflip($source, IMG_FLIP_VERTICAL);
        }
        $angle = match ($orientation) { 3 => 180, 5, 6, 7 => -90, 8 => 90, default => 0 };
        if ($angle !== 0 && ($turned = imagerotate($source, $angle, 0))) {
            imagedestroy($source);
            $source = $turned;
        }

        [$x, $y, $w, $h] = $crop;
        $width  = imagesx($source);
        $height = imagesy($source);
        $sx = max(0, min((int) round($x * $width), $width - 1));
        $sy = max(0, min((int) round($y * $height), $height - 1));
        $sw = max(1, min((int) round($w * $width), $width - $sx));
        $sh = max(1, min((int) round($h * $height), $height - $sy));

        // Never blown up; a big part brought down to MAX on its longer side.
        $k  = min(1.0, self::MAX / max($sw, $sh));
        $ow = max(1, (int) round($sw * $k));
        $oh = max(1, (int) round($sh * $k));

        $out = imagecreatetruecolor($ow, $oh);
        // A transparent picture gets white, not black.
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $source, 0, 0, $sx, $sy, $ow, $oh, $sw, $sh);

        ob_start();
        imagejpeg($out, null, 88);
        $jpeg = (string) ob_get_clean();

        imagedestroy($out);
        imagedestroy($source);

        return $jpeg !== '' ? $jpeg : null;
    }
}
