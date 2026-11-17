<?php

namespace App\Support\SchoolPdf;

/**
 * The bundled Poppins (resources/fonts, with the Hindi glyphs every PDF
 * prints shaped words in) as TCPDF fonts, made once into storage/fonts/tcpdf
 * and kept there — a new copy whenever a face file changes.
 */
class Fonts
{
    private const FACES = ['Poppins-Regular.ttf', 'Poppins-SemiBold.ttf'];

    /** @return array{0: array{string, string}, 1: array{string, string}}|null [[name, definition file] regular, semibold] */
    public static function poppins(): ?array
    {
        static $done = false, $fonts = null;
        if ($done) {
            return $fonts;
        }
        $done = true;

        try {
            $out = [];
            foreach (self::FACES as $face) {
                $src = resource_path('fonts/' . $face);
                if (!is_file($src)) {
                    return null;
                }
                $dir  = storage_path('fonts/tcpdf/' . substr(md5_file($src), 0, 10)) . '/';
                $name = strtolower(preg_replace('/[^a-z0-9_]/i', '', pathinfo($face, PATHINFO_FILENAME)));
                if (!is_file($dir . $name . '.php')) {
                    self::make($src, $dir, $name);
                }
                $out[] = [$name, $dir . $name . '.php'];
            }

            return $fonts = $out;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Convert one face in a folder of its own, then move it in — never half-written. */
    private static function make(string $src, string $dir, string $name): void
    {
        $tmp = rtrim(sys_get_temp_dir(), '/\\') . '/tcpdf-font-' . bin2hex(random_bytes(4)) . '/';
        @mkdir($tmp, 0775, true);
        @mkdir($dir, 0775, true);

        $made = \TCPDF_FONTS::addTTFfont($src, 'TrueTypeUnicode', '', 32, $tmp);
        if (!$made) {
            throw new \RuntimeException("TCPDF could not convert {$src}.");
        }

        // The definition file last: it is what says the font is there.
        foreach (['.z', '.ctg.z', '.php'] as $ext) {
            if (is_file($tmp . $made . $ext)) {
                $to = $dir . $name . $ext;
                if ($ext === '.php') {
                    // The definition names its data files; they keep this font's name.
                    $php = str_replace("'" . $made . '.', "'" . $name . '.', (string) file_get_contents($tmp . $made . $ext));
                    file_put_contents($tmp . $made . $ext, $php);
                }
                @rename($tmp . $made . $ext, $to) || copy($tmp . $made . $ext, $to);
            }
        }
        @array_map('unlink', glob($tmp . '*') ?: []);
        @rmdir($tmp);
    }
}
