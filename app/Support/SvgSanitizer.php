<?php

namespace App\Support;

/**
 * Makes an uploaded SVG safe to serve from our own domain: an SVG is a
 * document that can carry scripts, which would run if someone opened the
 * file's link directly. Kept: the drawing. Dropped: scripts, embedded HTML,
 * event attributes (onload…), links that aren't to the drawing itself or to
 * an embedded image, external stylesheets, entities and DOCTYPEs.
 */
class SvgSanitizer
{
    private const DROP_ELEMENTS = [
        'script', 'foreignobject', 'iframe', 'embed', 'object', 'handler', 'listener', 'set',
    ];

    /** The cleaned SVG, or null when it isn't one we can read. */
    public static function clean(string $svg): ?string
    {
        $svg = trim($svg);

        // Entities can read files or blow up memory; a logo never needs them.
        if ($svg === '' || stripos($svg, '<!DOCTYPE') !== false || stripos($svg, '<!ENTITY') !== false) {
            return null;
        }

        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') {
            return null;
        }

        $xpath = new \DOMXPath($doc);

        foreach (iterator_to_array($xpath->query('//processing-instruction()')) as $pi) {
            $pi->parentNode?->removeChild($pi);
        }

        foreach (iterator_to_array($xpath->query('//*')) as $el) {
            $name = strtolower($el->localName);

            if (in_array($name, self::DROP_ELEMENTS, true)) {
                $el->parentNode?->removeChild($el);
                continue;
            }

            // An animation that rewrites a link is a script by another name.
            if (str_starts_with($name, 'animate') && stripos((string) $el->getAttribute('attributeName'), 'href') !== false) {
                $el->parentNode?->removeChild($el);
                continue;
            }

            if ($name === 'style' && preg_match('/@import|javascript:|expression\s*\(/i', $el->textContent)) {
                $el->parentNode?->removeChild($el);
                continue;
            }

            foreach (iterator_to_array($el->attributes) as $attr) {
                $attrName = strtolower($attr->nodeName);
                $value    = trim($attr->nodeValue);

                $unsafe = str_starts_with($attrName, 'on')
                    || (in_array($attrName, ['href', 'xlink:href', 'src'], true) && !self::safeLink($value))
                    || ($attrName === 'style' && preg_match('/javascript:|expression\s*\(|@import/i', $value));

                if ($unsafe) {
                    $el->removeAttributeNode($attr);
                }
            }
        }

        return $doc->saveXML($doc->documentElement) ?: null;
    }

    /** A link to part of the drawing itself, or an embedded raster image. */
    private static function safeLink(string $value): bool
    {
        return str_starts_with($value, '#')
            || (bool) preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $value);
    }
}
