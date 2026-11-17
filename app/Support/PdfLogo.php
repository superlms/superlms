<?php

namespace App\Support;

/** A school's logo as dompdf can load it: a URL as it is, a stored path from public storage. */
class PdfLogo
{
    public static function src(?string $logo): ?string
    {
        $logo = trim((string) $logo);
        if ($logo === '') {
            return null;
        }
        if (preg_match('#^(https?:|data:)#i', $logo)) {
            return $logo;
        }
        foreach ([public_path('storage/' . ltrim($logo, '/')), public_path(ltrim($logo, '/'))] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
