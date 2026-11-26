<?php

namespace App\Support;

/**
 * The picture of an empty screen (2026-11-26), one drawing for both panels:
 * the Users page's <x-empty-state> draws it inline and every other list's
 * empty message (partials/empty-states) as a background, so they are the
 * same picture to the pixel.
 *
 *   'add'    — two cards, a person on the front one, a teal + (nothing yet)
 *   'search' — the cards under a magnifier (a search or filter found nothing)
 *   'filter' — the cards with a teal funnel (use the filter first)
 */
final class EmptyStateArt
{
    /** The <svg> markup, 168 × 120. */
    public static function svg(string $kind = 'add', string $class = ''): string
    {
        $badge = match ($kind) {
            'search' => '<circle cx="126" cy="86" r="13" fill="#fff" stroke="#0d9488" stroke-width="3"/>'
                . '<path d="M135.5 95.5l8 8" stroke="#0d9488" stroke-width="4" stroke-linecap="round"/>'
                . '<path d="M121 86h10" stroke="#b1e6db" stroke-width="2.5" stroke-linecap="round"/>',
            'filter' => '<circle cx="132" cy="44" r="12" fill="#0d9488"/>'
                . '<path d="M125.6 38.8h12.8l-4.9 5.9v5.1l-3 1.9v-7z" fill="#fff" stroke="#fff" stroke-width="1" stroke-linejoin="round"/>',
            default  => '<circle cx="132" cy="44" r="12" fill="#0d9488"/>'
                . '<path d="M132 38.5v11M126.5 44h11" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>',
        };

        return '<svg xmlns="http://www.w3.org/2000/svg"' . ($class !== '' ? ' class="' . e($class) . '"' : '')
            . ' viewBox="0 0 168 120" fill="none" aria-hidden="true">'
            . '<circle cx="84" cy="62" r="52" fill="#effaf7"/>'
            . '<circle cx="84" cy="62" r="52" stroke="#d8f3ec" stroke-dasharray="3 6"/>'
            . '<circle cx="28" cy="94" r="3" fill="#b1e6db"/>'
            . '<circle cx="146" cy="30" r="2.5" fill="#b1e6db"/>'
            . '<circle cx="140" cy="98" r="2" fill="#d8f3ec"/>'
            . '<rect x="38" y="30" width="80" height="48" rx="11" fill="#fff" stroke="#d8f3ec" stroke-width="1.5" transform="rotate(-7 78 54)"/>'
            . '<rect x="50" y="44" width="84" height="50" rx="11" fill="#fff" stroke="#b1e6db" stroke-width="1.5"/>'
            . '<circle cx="70" cy="69" r="11" fill="#d8f3ec"/>'
            . '<circle cx="70" cy="66" r="4" fill="#0d9488"/>'
            . '<path d="M62.5 76.5c1.6-3.4 4.4-5 7.5-5s5.9 1.6 7.5 5" stroke="#0d9488" stroke-width="2" stroke-linecap="round"/>'
            . '<rect x="88" y="61" width="34" height="6" rx="3" fill="#b1e6db"/>'
            . '<rect x="88" y="72" width="22" height="5" rx="2.5" fill="#d8f3ec"/>'
            . $badge
            . '</svg>';
    }

    /** The same picture as a CSS url("data:…") value. */
    public static function cssUrl(string $kind = 'add'): string
    {
        return 'url("data:image/svg+xml,' . rawurlencode(self::svg($kind)) . '")';
    }
}
