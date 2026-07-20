<?php

namespace App\Models\SuperAdmin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /** True when this document is visible to the given organization id. */
    public function visibleToOrg(int $orgId): bool
    {
        if ($this->audience_scope === 'all') {
            return true;
        }

        return $this->organizations->contains('id', $orgId);
    }

    /** Limit a query to documents visible to a given organization. */
    public function scopeForOrganization($query, int $orgId)
    {
        return $query->where(function ($q) use ($orgId) {
            $q->where('audience_scope', 'all')
              ->orWhereHas('organizations', fn ($o) => $o->where('organizations.id', $orgId));
        });
    }
}
