<?php

namespace App\Support;

/**
 * How a seat is written down, everywhere it is written down.
 *
 * The room comes first, then the desk read as column-letter + row-number, then
 * the place at that desk in brackets:
 *
 *     1- A1 (1)     room 1, column A, row 1, first place at the desk
 *
 * Columns are lettered because they run left to right as you face the board,
 * which is the order an invigilator walks them in; rows are numbered front to
 * back. A desk that seats one still carries its (1), so every seat on a sheet
 * reads the same way.
 */
class SeatLabel
{
    /** "A1" — the desk on its own, column letter then row number. */
    public static function seat(?int $rowNo, ?int $colNo): string
    {
        if (!$rowNo || !$colNo) {
            return '—';
        }

        return static::column($colNo) . $rowNo;
    }

    /** "1- A1 (1)" — the whole thing: room, desk, and the place at it. */
    public static function full(?string $room, ?int $rowNo, ?int $colNo, ?int $position = 1): string
    {
        $desk = static::seat($rowNo, $colNo);

        if ($desk === '—') {
            return $room ? $room . '-' : '—';
        }

        return ($room ? $room . '- ' : '') . $desk . ' (' . max(1, (int) $position) . ')';
    }

    /** 1 → A, 26 → Z, 27 → AA. */
    public static function column(int $colNo): string
    {
        $letters = '';
        while ($colNo > 0) {
            $colNo--;
            $letters = chr(65 + ($colNo % 26)) . $letters;
            $colNo = intdiv($colNo, 26);
        }

        return $letters ?: 'A';
    }
}
