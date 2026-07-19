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
        'paragraphs',
    ];

    protected $casts = [
        'paragraphs' => 'array',
    ];

    /**
     * The article body as a list of paragraphs. Prefers the structured
     * `paragraphs` column; falls back to splitting the legacy `description`
     * text on blank lines so older posts keep rendering correctly.
     *
     * @return array<int,string>
     */
    public function getBodyParagraphsAttribute(): array
    {
        $paras = array_values(array_filter(
            array_map('trim', (array) ($this->paragraphs ?? [])),
            fn($p) => $p !== ''
        ));

        if (!empty($paras)) {
            return $paras;
        }

        // Legacy fallback: split description into paragraphs on blank lines.
        $desc = trim((string) ($this->description ?? ''));
        if ($desc === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/', $desc)),
            fn($p) => $p !== ''
        ));
    }

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

    /** Short plain-text excerpt for cards (Markdown links collapse to their label). */
    public function getExcerptAttribute(): string
    {
        $source = $this->description ?: implode(' ', $this->body_paragraphs);
        $text   = preg_replace('~\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)~i', '$1', $source ?? '');

        return Str::limit(trim(strip_tags($text)), 140);
    }
}
