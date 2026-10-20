<?php

namespace App\Support;

/**
 * A phone photo is stored the way the camera was held, with a note (EXIF
 * "Orientation") saying how to turn it. Browsers turn it; GD does not, so a
 * picture made from it came out on its side. This reads the note from the
 * bytes themselves — no exif extension needed — and turns the image upright.
 */
class PhotoOrientation
{
    /** The JPEG's EXIF orientation, 1 to 8 (1 = as stored); anything else is 1. */
    public static function of(string $data): int
    {
        if (substr($data, 0, 2) !== chr(0xFF) . chr(0xD8)) {
            return 1;
        }

        $len = strlen($data);
        $i   = 2;
        while ($i + 4 <= $len && $data[$i] === chr(0xFF)) {
            $marker = ord($data[$i + 1]);
            $size   = unpack('n', substr($data, $i + 2, 2))[1];

            if ($marker === 0xE1 && substr($data, $i + 4, 4) === 'Exif') {
                return self::fromTiff(substr($data, $i + 10, max(0, $size - 8)));
            }
            if ($marker === 0xDA) {
                return 1; // the picture itself starts: no EXIF came before it
            }
            $i += 2 + $size;
        }

        return 1;
    }

    /** The Orientation tag (0x0112) of the first directory of an EXIF block. */
    private static function fromTiff(string $tiff): int
    {
        $little = substr($tiff, 0, 2) === 'II';
        $u16 = fn (int $o): int => strlen($tiff) >= $o + 2 ? unpack($little ? 'v' : 'n', substr($tiff, $o, 2))[1] : 0;
        $u32 = fn (int $o): int => strlen($tiff) >= $o + 4 ? unpack($little ? 'V' : 'N', substr($tiff, $o, 4))[1] : 0;

        $dir   = $u32(4);
        $count = $u16($dir);
        for ($e = 0; $e < $count; $e++) {
            $at = $dir + 2 + $e * 12;
            if ($u16($at) === 0x0112) {
                $o = $u16($at + 8);

                return $o >= 1 && $o <= 8 ? $o : 1;
            }
        }

        return 1;
    }

    /** The image as it should be seen: flipped (2, 4, 5, 7) and turned as the orientation says. */
    public static function upright(\GdImage $im, int $orientation): \GdImage
    {
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($im, IMG_FLIP_HORIZONTAL);
        }

        // imagerotate turns anticlockwise.
        $angle = match ($orientation) {
            3, 4    => 180,
            6, 7    => -90,
            5, 8    => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $im;
        }

        $turned = imagerotate($im, $angle, 0);
        if (!$turned) {
            return $im;
        }
        imagedestroy($im);

        return $turned;
    }
}
