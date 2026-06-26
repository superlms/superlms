<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    protected $fillable = [
        'slug',
        'cover_image',
        'category',
        'title',
        'heading',
        'description',
    ];

    /** Use the slug for route-model binding (clean blog detail URLs). */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Generate a unique slug from a title. */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'blog';
        $slug = $base;
        $i    = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    /** Short plain-text excerpt for cards. */
    public function getExcerptAttribute(): string
    {
        return Str::limit(trim(strip_tags($this->description ?? '')), 140);
    }
}
