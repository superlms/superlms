<?php

namespace App\Support;

use App\Models\Student\Standard;

/**
 * The board classes — Class 9 to Class 12 — of a school, read off the names
 * its classes are given on the Standard page ("Class 9", "IX", "10th",
 * "Class XI Science", "Twelfth"…). The website's Boards login and the
 * admin's More → Boards both take their students from these classes only
 * (user's ask of 2026-11-25).
 */
class BoardClasses
{
    public const FROM = 9;
    public const TO   = 12;

    private const ROMAN = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6, 'VII' => 7,
        'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12];

    private const WORDS = ['nine' => 9, 'ninth' => 9, 'ten' => 10, 'tenth' => 10, 'eleven' => 11,
        'eleventh' => 11, 'twelve' => 12, 'twelfth' => 12];

    /**
     * The class number a class name stands for (1–12), or null for one that
     * names none (Nursery, LKG…). A number written out wins over a roman
     * numeral, and that over a word.
     */
    public static function level(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        // A number on its own ("10", "10th", "10-A"), not a part of a year (2025-26).
        if (preg_match_all('/(?<![\d.])(\d{1,2})(?:st|nd|rd|th)?(?![\d.])/i', $name, $m)) {
            foreach ($m[1] as $n) {
                if ((int) $n >= 1 && (int) $n <= 12) {
                    return (int) $n;
                }
            }

            return null;
        }

        if (preg_match('/(?<![a-z])(xii|xi|ix|x|viii|vii|vi|iv|v|iii|ii|i)(?![a-z])/i', $name, $m)) {
            return self::ROMAN[strtoupper($m[1])];
        }

        if (preg_match('/\b(' . implode('|', array_keys(self::WORDS)) . ')\b/i', $name, $m)) {
            return self::WORDS[strtolower($m[1])];
        }

        return null;
    }

    /** Whether a class name is one of Class 9 to 12. */
    public static function isBoard(?string $name): bool
    {
        $level = self::level($name);

        return $level !== null && $level >= self::FROM && $level <= self::TO;
    }

    /** The school's Class 9–12 classes (active or not), in the Standard page's order. */
    public static function standards(int $orgId)
    {
        return Standard::where('organization_id', $orgId)
            ->inClassOrder()
            ->get(['id', 'name', 'order', 'is_active'])
            ->filter(fn ($s) => self::isBoard($s->name))
            ->values();
    }

    /** Their ids. */
    public static function standardIds(int $orgId): array
    {
        return self::standards($orgId)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
