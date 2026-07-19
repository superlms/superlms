<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;

class CreditPolicy extends Model
{
    protected $fillable = [
        'title',
        'content',
        'paragraphs',
        'image',
        'link',
        'document',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'paragraphs' => 'array',
    ];

    /**
     * The policy body as a list of paragraphs. Prefers the structured
     * `paragraphs` column; falls back to splitting the legacy `content`
     * text on blank lines so older policies keep rendering correctly.
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

        $content = trim((string) ($this->content ?? ''));
        if ($content === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/', $content)),
            fn($p) => $p !== ''
        ));
    }
}
