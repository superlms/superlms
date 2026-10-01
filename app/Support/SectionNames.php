<?php

namespace App\Support;

/**
 * How a list reads several sections in one line: the first section's name in
 * full, then each further section by the last letter of its name, joined with
 * "&" — "Section A & B & C". A single section reads as its own name.
 */
class SectionNames
{
    /** @param iterable<string|null> $names */
    public static function joined(iterable $names): string
    {
        $unique = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name !== '' && !in_array($name, $unique, true)) {
                $unique[] = $name;
            }
        }

        if (!$unique) {
            return '';
        }

        $parts = [array_shift($unique)];
        foreach ($unique as $name) {
            $parts[] = mb_strtoupper(mb_substr($name, -1));
        }

        return implode(' & ', $parts);
    }
}
