<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A document a school-admin uploads and manages for their own organization.
 * Scoped to a single organization; admins can add / edit / download / delete
 * their own documents. Distinct from {@see \App\Models\SuperAdmin\SuperAdminDocument},
 * which the super-admin pushes down to schools (read-only for admins).
 */
class AdminDocument extends Model
{
    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Public S3 URL for inline viewing (images / PDFs open in a new tab). */
    public function getUrlAttribute(): string
    {
        return $this->file_path ? Storage::disk('s3')->url($this->file_path) : '';
    }

    /** Human-readable file size, e.g. "1.4 MB". */
    public function getReadableSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        $i = max(0, min($i, count($units) - 1));

        return round($bytes / (1024 ** $i), $i ? 1 : 0) . ' ' . $units[$i];
    }

    /** Limit a query to a single organization's own documents. */
    public function scopeForOrganization($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }
}
