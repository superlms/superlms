<?php

namespace App\Support;

/**
 * A school's board as the Super Admin lists show it: the board alone. The
 * Add School form's choices carry the medium in brackets ("UP BOARD (HINDI &
 * ENGLISH MEDIUM)"); lists drop that part ("UP BOARD"). The saved value is
 * left as it is.
 */
class BoardName
{
    public static function short(?string $board): string
    {
        $board = trim((string) $board);
        if ($board === '') {
            return '';
        }

        $short = trim(preg_replace('/\s*\([^)]*medium[^)]*\)\s*$/i', '', $board));

        return $short !== '' ? $short : $board;
    }
}
