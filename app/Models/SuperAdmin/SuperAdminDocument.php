<?php

namespace App\Models\SuperAdmin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * A document the super-admin uploads and pushes down to schools. It targets
 * either every organization (`audience_scope = all`) or a specific set of
 * organizations (`audience_scope = selected`, resolved via the pivot). School
 * admins see the documents visible to their own organization and can view /
 * download them.
 */
class SuperAdminDocument extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'audience_scope',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'super_admin_document_organization',
            'document_id',
            'organization_id'
        );
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The files in this document, each with its own name, in their order
     * (2026-11-26). The document's own file columns hold the first of them.
     */
    public function files(): HasMany
    {
        return $this->hasMany(SuperAdminDocumentFile::class, 'super_admin_document_id')
            ->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Its files — from super_admin_document_files, or (a document from before
     * that table, not moved yet) its own file as the only one, unsaved.
     */
    public function fileList(): Collection
    {
        $files = $this->relationLoaded('files') ? $this->files : $this->files()->get();
        if ($files->isNotEmpty() || !$this->file_path) {
            return $files;
        }

        return collect([new SuperAdminDocumentFile([
            'super_admin_document_id' => $this->id,
            'name'                    => $this->title,
            'file_path'               => $this->file_path,
            'file_name'               => $this->file_name,
            'file_size'               => $this->file_size,
            'mime_type'               => $this->mime_type,
        ])]);
    }

    /** All its files' size together, e.g. "2.1 MB". */
    public function totalSize(): string
    {
        return self::readableBytes((int) $this->fileList()->sum('file_size'));
    }

    /** Public S3 URL for inline viewing (images / PDFs open in a new tab). */
    public function getUrlAttribute(): string
    {
        return $this->file_path ? Storage::disk('s3')->url($this->file_path) : '';
    }

    /** Human-readable file size, e.g. "1.4 MB". */
    public function getReadableSizeAttribute(): string
    {
        return self::readableBytes((int) $this->file_size);
    }

    /** 1468006 → "1.4 MB"; nothing → "—". */
    public static function readableBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        $i = max(0, min($i, count($units) - 1));

        return round($bytes / (1024 ** $i), $i ? 1 : 0) . ' ' . $units[$i];
    }

    /** True when the super-admin kept this document instead of sending it. */
    public function isPrivate(): bool
    {
        return $this->audience_scope === 'private';
    }

    /** True when this document is visible to the given organization id. */
    public function visibleToOrg(int $orgId): bool
    {
        if ($this->isPrivate()) {
            return false;
        }

        if ($this->audience_scope === 'all') {
            return true;
        }

        return $this->organizations->contains('id', $orgId);
    }

    /** Limit a query to documents visible to a given organization. */
    public function scopeForOrganization($query, int $orgId)
    {
        // A 'private' document matches neither branch, so it stays hidden.
        return $query->where(function ($q) use ($orgId) {
            $q->where('audience_scope', 'all')
              ->orWhereHas('organizations', fn ($o) => $o->where('organizations.id', $orgId));
        });
    }
}
