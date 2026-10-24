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

    /**
     * The room's word cut short, for the admit card's narrow Room / Seat
     * column: "ROOM 15- C8 (1)" → "R15- C8 (1)", "Exam Hall 2- A3 (1)" →
     * "EH2- A3 (1)". Works on a room name or a whole label; any other name
     * ("Lab 2", "Hall 3") is left as it is.
     */
    public static function shortRoom(?string $label): string
    {
        $label = trim((string) $label);
        if ($label === '') {
            return '—';
        }

        // The space (or "No.", ":", "#", "-") between the word and the room's
        // number goes; the "- " before the desk stays.
        $gap = '(?:[\s.:#]|-(?=\s*\d))*';
        $short = preg_replace('/^exam[\s._-]*hall(?![a-z])' . $gap . '/iu', 'EH', $label, 1);
        if ($short === $label) {
            $short = preg_replace('/^room(?![a-z])(?:[\s.]*(?:no\.?|number)(?![a-z]))?' . $gap . '/iu', 'R', $label, 1);
        }

        return $short ?? $label;
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
