<?php

namespace App\Models\Student;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Topic extends Model
{
    protected $fillable = ['organization_id', 'chapter_id', 'topic_name', 'topic_content', 'order', 'image_path', 'pdf_path', 'link'];

    // Always expose ready-to-use URLs so the app/admin/student all get the same
    // thing regardless of whether the column holds a raw S3 path or a full URL.
    protected $appends = ['image_url', 'pdf_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->resolveUrl($this->image_path);
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->resolveUrl($this->pdf_path);
    }

    private function resolveUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        if (Str::startsWith($value, ['http://', 'https://', 'data:'])) {
            return $value;
        }
        return Storage::disk('s3')->url($value);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}
