<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Where a school is, read from what its admin pastes into Website Data's map:
 * a Google Maps link — a phone's share link (maps.app.goo.gl/…), a place or
 * search link, an embed link or the whole <iframe> code — or the coordinates
 * Google Maps shows on a long-press ("27.9512, 78.1234").
 *
 * parse() gives ['lat' => …, 'lng' => …] for a point, ['embed' => …] for a
 * map that names a place without its coordinates, or null for neither.
 */
class MapLink
{
    /** Google's short share links, followed to the link they stand for. */
    private const SHORT_HOSTS = ['maps.app.goo.gl', 'goo.gl', 'g.co'];

    /** @param callable(string): string|null $follow where a short link leads */
    public static function parse(string $input, ?callable $follow = null): ?array
    {
        $input = trim($input);
        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $input, $m)) {
            $input = html_entity_decode($m[1]);
        }
        if ($input === '') {
            return null;
        }
        if ($point = self::point($input)) {
            return $point;
        }
        if (! preg_match('#^https?://#i', $input)) {
            return null;
        }

        if (in_array(self::host($input), self::SHORT_HOSTS, true)) {
            $input = trim((string) ($follow ?? [self::class, 'follow'])($input));
        }
        if (! preg_match('/(^|\.)google\.[a-z]{2,3}(\.[a-z]{2})?$/', self::host($input))) {
            return null;
        }

        $url  = rawurldecode($input);
        $path = (string) parse_url($input, PHP_URL_PATH);
        parse_str((string) parse_url($input, PHP_URL_QUERY), $q);

        // A place's own pin (…!3d27.95!4d78.12) before the view's centre (@27.95,78.12,17z).
        if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $url, $m) && ($p = self::point("{$m[1]},{$m[2]}"))) {
            return $p;
        }
        foreach (['q', 'query', 'll', 'center', 'destination'] as $k) {
            if (is_string($q[$k] ?? null) && ($p = self::point($q[$k]))) {
                return $p;
            }
        }
        if (preg_match('#/maps/(?:place|search)/([^/@]+)#', $path, $m) && ($p = self::point(urldecode($m[1])))) {
            return $p;
        }
        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $url, $m) && ($p = self::point("{$m[1]},{$m[2]}"))) {
            return $p;
        }

        if (str_starts_with($path, '/maps/embed')) {
            return ['embed' => $input];
        }

        // Only a name to go by: the map searches for it.
        $name = '';
        foreach (['q', 'query'] as $k) {
            if (is_string($q[$k] ?? null) && trim($q[$k]) !== '') {
                $name = $q[$k];
                break;
            }
        }
        if ($name === '' && preg_match('#/maps/(?:place|search)/([^/@]+)#', $path, $m)) {
            $name = urldecode($m[1]);
        }
        $name = trim($name);

        return $name !== '' ? ['embed' => 'https://maps.google.com/maps?q=' . urlencode($name) . '&z=16&output=embed'] : null;
    }

    /** "27.9512, 78.1234" → the point, when both are in range (and not 0, 0). */
    public static function point(string $text): ?array
    {
        if (! preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $text, $m)) {
            return null;
        }

        return self::pin((float) $m[1], (float) $m[2]);
    }

    /** A point from two numbers, to six places; null when out of range. */
    public static function pin(float $lat, float $lng): ?array
    {
        if (abs($lat) > 90 || abs($lng) > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return null;
        }
        $fmt = fn (float $v) => rtrim(rtrim(number_format($v, 6, '.', ''), '0'), '.');

        return ['lat' => $fmt($lat), 'lng' => $fmt($lng)];
    }

    /** Where a short link leads, its redirects followed; '' when it cannot be reached. */
    public static function follow(string $url): string
    {
        try {
            $res = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; SuperLMS)'])
                ->withOptions(['allow_redirects' => ['max' => 5, 'protocols' => ['https']]])
                ->get($url);

            return (string) ($res->effectiveUri() ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    private static function host(string $url): string
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST));
    }
}
