<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One file in a Super Admin document, with the name it was given there
 * (the document has the title and the description; 2026-11-26).
 */
class SuperAdminDocumentFile extends Model
{
    protected $fillable = [
        'super_admin_document_id',
        'name',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'sort_order',
    ];

    protected $casts = [
        'file_size'  => 'integer',
        'sort_order' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SuperAdminDocument::class, 'super_admin_document_id');
    }

    /** Public S3 URL for inline viewing (images / PDFs open in a new tab). */
    public function getUrlAttribute(): string
    {
        return $this->file_path ? Storage::disk('s3')->url($this->file_path) : '';
    }

    /** Human-readable file size, e.g. "1.4 MB". */
    public function getReadableSizeAttribute(): string
    {
        return SuperAdminDocument::readableBytes((int) $this->file_size);
    }

    /** The name to download it under: its own name with the file's extension. */
    public function downloadName(): string
    {
        $ext  = pathinfo((string) $this->file_name, PATHINFO_EXTENSION);
        $name = trim(str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', (string) $this->name));

        if ($name === '') {
            return (string) $this->file_name;
        }

        return $ext !== '' && !str_ends_with(strtolower($name), '.' . strtolower($ext)) ? $name . '.' . $ext : $name;
    }
}
