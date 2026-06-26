<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A dynamic marketing page (why-us, services, careers, become-executive,
 * blogs, faqs). Content is stored in the `metadata` JSON column and managed
 * from the super-admin panel.
 */
class WebsitePage extends Model
{
    protected $fillable = [
        'slug',
        'metadata',
        'last_updated',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'last_updated' => 'date',
    ];

    /** Convenience: fetch a page's metadata array by slug (or null). */
    public static function meta(string $slug): ?array
    {
        return static::where('slug', $slug)->first()?->metadata;
    }
}
