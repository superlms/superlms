<?php

namespace App\Support;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Small square photos for a PDF, as JPEG bytes.
 *
 * Student photos are mostly whole camera shots (a few thousand pixels, about a
 * megabyte each). Handing those URLs to dompdf makes it fetch and decode every
 * one in full and embed it at full size, so a school's PDF ran past the
 * gateway's minute and failed. Here each photo is fetched once — several at a
 * time — cut to a small square JPEG and kept on disk, so the next export reads
 * it straight back. A camera shot's own EXIF thumbnail is used when it matches
 * the photo, which spares decoding the whole picture.
 *
 * The work stops at a time limit so the PDF is always made: a photo not reached
 * in time is left out (the PDF shows the initial instead) and is ready the next
 * time, as everything reached so far has been kept.
 */
class PdfPhotos
{
    private const DIR = 'pdf-photos';

    /**
     * @param  string[]  $urls
     * @return array<string,string>  url => JPEG bytes
     */
    public static function squares(array $urls, int $size = 120, float $seconds = 25.0): array
    {
        $started = microtime(true);
        $out     = [];
        $missing = [];

        foreach (array_unique(array_filter($urls)) as $url) {
            $file = self::cacheFile($url, $size);
            if (is_file($file)) {
                $out[$url] = (string) file_get_contents($file);
            } else {
                $missing[] = $url;
            }
        }

        foreach (array_chunk($missing, 12) as $n => $batch) {
            if (microtime(true) - $started > $seconds) {
                logger()->info('pdf photos: time limit reached', ['not_reached' => count($missing) - $n * 12]);
                break;
            }

            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn ($url) => $pool->as($url)->timeout(8)->connectTimeout(3)->get(self::absolute($url)),
                $batch
            ));

            foreach ($batch as $url) {
                $response = $responses[$url] ?? null;
                if (! $response instanceof \Illuminate\Http\Client\Response || ! $response->successful()) {
                    continue;
                }

                $jpeg = self::square($response->body(), $size);
                if ($jpeg === null) {
                    continue;
                }

                $file = self::cacheFile($url, $size);
                if (! is_dir(dirname($file))) {
                    @mkdir(dirname($file), 0775, true);
                }
                @file_put_contents($file, $jpeg);
                $out[$url] = $jpeg;
            }
        }

        return $out;
    }

    /** The picture cut to a centred square of $size pixels, as JPEG bytes — or null. */
    public static function square(string $bytes, int $size): ?string
    {
        $info = @getimagesizefromstring($bytes);
        if (! $info) {
            return null;
        }
        [$width, $height] = $info;
        $isJpeg = ($info[2] ?? null) === IMAGETYPE_JPEG;

        $orientation = 1;
        $source      = null;

        if ($isJpeg && function_exists('exif_read_data')) {
            $stream = fopen('php://memory', 'r+b');
            fwrite($stream, $bytes);
            rewind($stream);
            $exif = @exif_read_data($stream);
            $orientation = (int) ($exif['Orientation'] ?? 1);

            // A camera shot carries a small preview of itself: use it when the
            // picture is big and the preview has its shape (a picture cropped
            // after it was taken keeps the old preview, so that one is skipped).
            if (max($width, $height) > 1200 && function_exists('exif_thumbnail')) {
                rewind($stream);
                $thumb = @exif_thumbnail($stream, $tw, $th);
                if ($thumb && $tw && $th && abs(($tw / $th) - ($width / $height)) < 0.05) {
                    $source = @imagecreatefromstring($thumb) ?: null;
                }
            }
            fclose($stream);
        }

        $source ??= @imagecreatefromstring($bytes) ?: null;
        if (! $source) {
            return null;
        }

        $angle = match ($orientation) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($angle !== 0 && ($turned = imagerotate($source, $angle, 0))) {
            imagedestroy($source);
            $source = $turned;
        }

        $w    = imagesx($source);
        $h    = imagesy($source);
        $side = min($w, $h);

        $square = imagecreatetruecolor($size, $size);
        imagefill($square, 0, 0, imagecolorallocate($square, 255, 255, 255));
        imagecopyresampled($square, $source, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), $size, $size, $side, $side);

        ob_start();
        imagejpeg($square, null, 80);
        $jpeg = (string) ob_get_clean();

        imagedestroy($square);
        imagedestroy($source);

        return $jpeg !== '' ? $jpeg : null;
    }

    private static function cacheFile(string $url, int $size): string
    {
        return storage_path('app/' . self::DIR . '/' . $size . '/' . sha1($url) . '.jpg');
    }

    /** A stored path becomes the disk's URL; a full URL stays as it is. */
    private static function absolute(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : Storage::disk('s3')->url($url);
    }
}
