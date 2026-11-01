<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The platform's own settings (table platform_settings) — for now its logo,
 * which the super admin uploads on Profile → Platform as an image or an SVG.
 * Every page that shows the SuperLMS logo asks logoUrl(); with none uploaded
 * it is the logo the site always had.
 */
class Platform
{
    public const DEFAULT_LOGO = 'website-image/Group 11525.png';

    private const CACHE_KEY = 'platform_settings';

    /** This request's settings, read once. */
    private static ?array $settings = null;

    public static function logoUrl(): string
    {
        return self::get('logo_url') ?: asset(self::DEFAULT_LOGO);
    }

    /** Whether a logo of the super admin's own is in use. */
    public static function hasCustomLogo(): bool
    {
        return (bool) self::get('logo_url');
    }

    public static function get(string $key): ?string
    {
        if (self::$settings === null) {
            try {
                self::$settings = Cache::rememberForever(self::CACHE_KEY, fn () => DB::table('platform_settings')->pluck('value', 'key')->all());
            } catch (\Throwable $e) {
                // No table yet (mid-deploy) — the defaults stand.
                self::$settings = [];
            }
        }

        $value = self::$settings[$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    public static function set(string $key, ?string $value): void
    {
        DB::table('platform_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget(self::CACHE_KEY);
        self::$settings = null;
    }

    /**
     * Store an uploaded logo (PNG, JPG, WEBP or SVG) on S3 and use it
     * everywhere. An SVG is cleaned first (SvgSanitizer). Returns its URL.
     *
     * @throws \InvalidArgumentException when the SVG can't be used
     */
    public static function storeLogo(UploadedFile $file): string
    {
        $disk = Storage::disk('s3');
        $ext  = strtolower($file->getClientOriginalExtension());

        if ($ext === 'svg') {
            $clean = SvgSanitizer::clean((string) $file->get());
            if ($clean === null) {
                throw new \InvalidArgumentException('This SVG could not be read — it must be a plain SVG drawing (no DOCTYPE, an <svg> at its root).');
            }
            $path = 'platform/logo/logo-' . Str::random(12) . '.svg';
            $disk->put($path, $clean, ['visibility' => 'public', 'ContentType' => 'image/svg+xml']);
        } else {
            $path = $file->storeAs('platform/logo', 'logo-' . Str::random(12) . '.' . $ext, 's3');
            $disk->setVisibility($path, 'public');
        }

        $old = self::get('logo_path');

        self::set('logo_path', $path);
        self::set('logo_url', $disk->url($path));

        self::deleteFile($old);

        return self::logoUrl();
    }

    /** Back to the logo the site always had. */
    public static function resetLogo(): void
    {
        $old = self::get('logo_path');

        self::set('logo_path', null);
        self::set('logo_url', null);

        self::deleteFile($old);
    }

    private static function deleteFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        try {
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Old platform logo not deleted', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }
}
